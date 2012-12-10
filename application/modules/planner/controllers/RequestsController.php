<?php

class Planner_RequestsController extends My_Controller_Action
{

    public $ajaxable = array(
        'index' => array('html'),
        'save-request' => array('json'),
    );

    /**
     * @var Application_Model_User
     */
    protected $_modelUser;
    /**
     * @var Application_Model_Request
     */
    protected $_modelRequest;

    public function init()
    {
        $this->view->me = $this->_me = $this->_helper->CurrentUser();
        $this->_modelUser = new Application_Model_User();
        $this->_modelRequest = new Application_Model_Request();

        if ( ! $this->_getParam('user')) {
            $this->_setParam('user', $this->_me['id']);
        } else {
            $allowed = false;
            if ($this->_me['role'] >= Application_Model_Auth::ROLE_ADMIN) {
                $allowed = true;
            }
            if ( ! $allowed) {
                $this->_setParam('user', $this->_me['id']);
            }
        }

        parent::init();
    }

    public function indexAction()
    {
        $userId = $this->_getParam('user');
        $userParameters = $this->_modelUser->getParametersByUserId($userId);
        $userRequests = $this->_modelRequest->getRequestsByUserId($userId);
        $blocked = $this->_getBlockedDatesByUserId($userId);
        $assign = array(
            'userRequests' => $userRequests,
            'blocked'      => $blocked,
        );
        $this->view->me['parameters'] = $userParameters;
        $this->view->assign($assign);
    }

    public function saveRequestAction()
    {
        $userId = $this->_getParam('user');
        $modelRequests = new Application_Model_Request();
        $selectedDates = $this->_getParam('selected_dates', array());
        $blocked = $this->_getBlockedDatesByUserId($userId);
        $check = array_intersect($blocked, $selectedDates);
        if (count($check)) {
            $status = false;
        } else {
            $modelPlanner = new Application_Model_Db_User_Planning();
//            $workDays = $modelPlanner->getUserDayPlanByGroup($userId, , )
            // TODO: Here need add check how much work hours need for selected dates. If more then user have then ERROR.
            $status = $modelRequests->saveRequest($userId, $selectedDates);
        }

        if ($status) {
            $this->_response(1, '', array());
        } else {
            $this->_response(0, 'Error!', array());
        }
    }

    protected function _getBlockedDatesByUserId($userId)
    {
        $blocked = array();
        $modelGroup = new Application_Model_Group();
        $groups = $modelGroup->getGroupsByUserId($userId);
        foreach ($groups as $group) {
            $groupBlocked = $modelGroup->getNotAllowedForRequestsDates($group['id']);
            foreach ($groupBlocked as $block) {
                try {
                    $date = new DateTime($block['calendar_date']);
                    $date = $date->format('Y-m-d');
                    $blocked[$date] = $date;
                } catch (Exception $e) {continue;}
            }
        }
        return $blocked;
    }

}