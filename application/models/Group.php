<?php

class Application_Model_Group extends Application_Model_Abstract
{

    protected $_modelDb;

    const GENERAL_GROUP_ID = 0;

    public function __construct()
    {
        $this->_modelDb = new Application_Model_Db_Groups();
    }

    public function getAllGroups()
    {
        $groups = $this->_modelDb->getAllGroups();
        return $groups;
    }

    public function getGroupById($groupId)
    {
        $group = $this->_modelDb->getGroupById($groupId);
        return $group;
    }

    public function getGroupsByUserId($userId)
    {
        $groups = $this->_modelDb->getGroupsByUserId($userId);
        return $groups;
    }

    public function getUserGroupsAdmin(array $user)
    {
        $admin = array();
        if (empty($user['role']) || empty($user['id'])) {
            return $admin;
        }
        if ($user['role'] == Application_Model_Auth::ROLE_GROUP_ADMIN) {
            $modelUserGroups = new Application_Model_Db_User_Groups();
            $admin = $modelUserGroups->getUserGroupsAdmin($user['id']);
        }
        return $admin;
    }

    public function saveGroup(array $group) {
        $result = false;
        if (Application_Model_Auth::getRole() >= Application_Model_Auth::ROLE_ADMIN) {
            if ( ! empty($group['id'])) {
                $oldGroup = $this->_modelDb->getGroupById($group['id']);
                if (empty($oldGroup)) {
                    return $result;
                }
                if ($oldGroup == $group) {
                    $result = true;
                    return $result;
                }
                $result = $this->_modelDb->updateGroup($group['id'], $group);
            } else {
                $result = $this->_modelDb->insertGroup($group);
            }
        }
        return $result;
    }

    public function deleteGroup($groupId)
    {
        if (Application_Model_Auth::getRole() >= Application_Model_Auth::ROLE_ADMIN) {
            $this->_modelDb->deleteGroup($groupId);
        }
        return true;
    }

    public function getGroupPlanning($groupId, $weekType = null, $day='')
    {
        $groupPlannings = new Application_Model_Db_Group_Plannings();
        $planning = $groupPlannings->getGroupPlanning($groupId, $weekType, $day);
        return $planning;
    }

    public function getGroupPlanningByDate($groupId, $date)
    {
        $currentDate = new My_DateTime($date);
        $dateWeekYear = My_DateTime::getWeekYear($currentDate->getTimestamp());
        $weekType = My_DateTime::getEvenWeek($dateWeekYear['week']);
        return $this->getGroupPlanning($groupId, $weekType, $dateWeekYear['day']);
    }

    public function saveGroupPlanning($groupId, array $planning, array $pause)
    {
        $result = false;
        if (Application_Model_Auth::getRole() >= Application_Model_Auth::ROLE_ADMIN) {
            $group = $this->_modelDb->getGroupById($groupId);
            if ($group) {
                $pause = $this->_preparePause($pause);
                $planning = $this->_preparePlanning($planning);
                $groupPlannings = new Application_Model_Db_Group_Plannings();
                $result = $groupPlannings->saveGroupPlanning($groupId, $planning);
                if ($result) {
                    $groupSettings = new Application_Model_Db_Group_Settings();
                    if ( ! empty($pause['pause_start']) && ! empty($pause['pause_end'])) {
                        $result = $groupSettings->saveGroupPause($groupId, $pause['pause_start'], $pause['pause_end']);
                    } else {
                        $result = $groupSettings->deleteGroupPause($groupId);
                    }
                }
            }
        }
        return $result;
    }

    public function getGroupSettings($groupId)
    {
        $groupSettings = new Application_Model_Db_Group_Settings();
        $settings = $groupSettings->getGroupSettings($groupId);
        return $settings;
    }

    public function saveGroupSetting($groupId, $setting, $value)
    {
        $groupSettings = new Application_Model_Db_Group_Settings();
        $result = $groupSettings->saveGroupSetting($groupId, $setting, $value);
        return $result;
    }

    public function getGeneralGroup()
    {
        $data = array(
            'id'         => self::GENERAL_GROUP_ID,
            'group_name' => 'General Group',
            'color'      => 'FFFFFF',
        );
        return $data;
    }

    public function getGeneralGroupSettings()
    {
        $groupSettings = new Application_Model_Db_Group_Settings();
        $settings = $groupSettings->getDefaultGroupSettings();
        return $settings;
    }

    public function getExceptions($groupId)
    {
        $groupExceptions = new Application_Model_Db_Group_Exceptions();
        $exceptions = $groupExceptions->getExceptions($groupId);
        return $exceptions;
    }

    public function getGeneralExceptions()
    {
        // Yes. This method is copy of getExceptions but with $groupId = 0.
        $groupExceptions = new Application_Model_Db_Group_Exceptions();
        $exceptions = $groupExceptions->getExceptions(0);
        return $exceptions;
    }

    public function groupExceptions(array $exceptions, $year = null)
    {
        $grouped = array();
        $lastDate = null;
        $lastMaxFreePeople = null;
        $id = 0;
        foreach ($exceptions as $exception) {
            try {
                $date = new DateTime($exception['exception_date']);
                if ($year && $date->format('Y') != $year) {
                    continue;
                }
                $prevDate = clone $date;
                $prevDate->modify('-1 days');
                if ($prevDate != $lastDate || (int)$exception['max_free_people'] !== $lastMaxFreePeople) {
                    $id++;
                }
                $grouped[$id][] = $exception;
                $lastDate = $date;
                $lastMaxFreePeople = (int)$exception['max_free_people'];
            } catch (Exception $e) { continue; }
        }
        return $grouped;
    }

    public function saveGroupExceptions($groupId, $oldSelectedDates, $selectedDates, $maxFreePeople)
    {
        $modelDbGroupExceptions = new Application_Model_Db_Group_Exceptions();
        if (count($oldSelectedDates) > 0 ) {
            $result = $modelDbGroupExceptions->editGroupExceptions($groupId, $oldSelectedDates, $selectedDates, $maxFreePeople);
        } else {
            $result = $modelDbGroupExceptions->insertGroupExceptions($groupId, $selectedDates, $maxFreePeople);
        }
        return $result;
    }

    protected function _preparePlanning(array $planning)
    {
        $prepared = array();
        foreach ($planning as $key => $day) {
            if (empty($day['time_start']['hour']) || empty($day['time_end']['hour'])) {
                continue;
            }
            try { $timeStart = new DateTime((int)$day['time_start']['hour'] . ':' . (int)$day['time_start']['min']); } catch (Exception $e) { $timeStart = ''; }
            try { $timeEnd = new DateTime((int)$day['time_end']['hour'] . ':' . (int)$day['time_end']['min']); } catch (Exception $e) { $timeEnd = ''; }
            if ( ! $timeStart || ! $timeEnd || $timeStart >= $timeEnd) {
                continue; // wrong time
            }
            $day['day_number'] = intval($day['day_number']);
            if ($day['day_number'] < 1 || $day['day_number'] > 6 ) {
                continue; // wrong day number
            }
            if ($day['week_type'] != Application_Model_Db_Group_Plannings::WEEK_TYPE_ODD && $day['week_type'] != Application_Model_Db_Group_Plannings::WEEK_TYPE_EVEN) {
                continue; // wrong week type
            }
            $preparedDay = array(
                'week_type'  => $day['week_type'],
                'day_number' => $day['day_number'],
                'time_start' => $timeStart->format('H:i:s'),
                'time_end'   => $timeEnd->format('H:i:s'),
            );
            $prepared[$key] = $preparedDay;
        }
        return $prepared;
    }

    protected function _preparePause(array $pause)
    {
        $prepared = array();
        if ( ! empty($pause['pause_start']['hour']) && ! empty($pause['pause_end']['hour'])) {
            try { $pauseStart = new DateTime((int)$pause['pause_start']['hour'] . ':' . (int)$pause['pause_start']['min']); } catch (Exception $e) { $pauseStart = ''; }
            try { $pauseEnd = new DateTime((int)$pause['pause_end']['hour'] . ':' . (int)$pause['pause_end']['min']); } catch (Exception $e) { $pauseEnd = ''; }
            if ($pauseStart && $pauseEnd && $pauseStart < $pauseEnd) {
                $prepared['pause_start'] = $pauseStart->format('H:i:s');
                $prepared['pause_end']   = $pauseEnd->format('H:i:s');
            }
        }
        return $prepared;
    }

    static public function getAllowedColors()
    {
        $colors = array(
            'FFFFFF','DCDCDC','D3D3D3','C0C0C0','808080','708090',
            'FFFFE0','F5F5DC','FFF8DC','FFFACD','FAFAD2','FFEFD5',
            'FAEBD7','FFE4C4','FFDEAD','FFC0CB','FFE4E1','E6E6FA',
            'D8BFD8','B0C4DE','ADD8E6','B0E0E6','AFEEEE','E0FFFF',
            'F0F8FF','F0FFFF','F0FFF0','90EE90','3CB371','DA70D6',
        );
        return $colors;
    }

}
