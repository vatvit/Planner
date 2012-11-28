<?php

class Application_Model_Db_User_Planning extends Application_Model_Db_Abstract
{
    const TABLE_NAME = 'user_planning';



    public function __construct()
    {
        parent::__construct();
    }

    public function getUserDayPlanByGroup($userId, $groupId, $date)
    {
        $select = $this->_db->select()
            ->from(array('up' => self::TABLE_NAME), array('*', 'total_time' => 'TIMEDIFF(time_end,time_start)'))
            ->where('up.user_id = ?', $userId)
            ->where('up.group_id = ?', $groupId)
            ->where('up.date = ?', $date);
            //->where('up.date <= ADDDATE( ?, INTERVAL 6 DAY)', $date)
        $result = $this->_db->fetchRow($select);
        return $result;
    }



    public function createNewDayUserPlanByGroup($dayPlan)
    {
        var_dump($dayPlan);
        try {
            $this->_db->insert(self::TABLE_NAME,$dayPlan);
        } catch (Exception $e ) {
            //for this day plan already exist
            echo "exist - use \n" ;
        }
    }

    public function getTotalWorkTimeByGroup($userId, $groupId, $date, $weekLenght = 7)
    {
        $select = $this->_db->select()
            ->from(
                array('up' => self::TABLE_NAME),
                array("SUM( TIME_FORMAT(TIMEDIFF(up.time_end, up.time_start), '%H') )")
            )
            ->where('up.user_id = ?', $userId)
            ->where('up.group_id = ?', $groupId)
            ->where('up.status1 = ?', Application_Model_Planning::STATUS_DAY_GREEN)
            ->where('up.date >= ?', $date)
            ->where('up.date <= ADDDATE( ?, INTERVAL ' . $weekLenght . ' DAY)', $date);
        $result = $this->_db->fetchOne($select);
        if (empty($result)) {
            $result = 0;
        }
        return $result;
    }

    public function getDayById($dayId)
    {
        $select = $this->_db->select()
            ->from(array('up' => self::TABLE_NAME))
            ->where('up.id = ?', $dayId);
        $result = $this->_db->fetchRow($select);
        return $result;
    }

    public function saveStatus($fields)
    {
        $id = $fields['id'];
        unset($fields['id']);
        unset($fields['use_status2']);
        unset($fields['color']);
        $this->_db->update(self::TABLE_NAME, $fields, array('id = ?' => $id));
        return true;
    }

    public function saveWorkTime($formData)
    {
        //TODO realize logic for update
        $this->_db->update(self::TABLE_NAME, $fields, array('id = ?' => $id));
    }
}
