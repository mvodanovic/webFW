<?php

namespace WebFW\CMS\Controllers;

use \WebFW\CMS\Controller;
use \WebFW\CMS\DBLayer\ListFetchers\User as LFUser;
use \WebFW\CMS\DBLayer\User as TGUser;
use \WebFW\Core\Classes\HTML\Input;
use \WebFW\Core\Classes\HTML\Textarea;
use \WebFW\Core\Classes\HTML\Select;
use \WebFW\CMS\Classes\EditTab;
use \WebFW\CMS\DBLayer\ListFetchers\UserType as LFUserType;
use \WebFW\CMS\DBLayer\UserType as TGUserType;
use \WebFW\CMS\Classes\ListHelper;
use WebFW\Core\Exception;

class User extends Controller
{
    public function init()
    {
        parent::init();

        $this->listFetcher = new LFUser();
        $this->tableGateway = new TGUser();
    }

    public function initList()
    {
        parent::initList();

        $this->sort = array(
            'user_id' => 'ASC',
        );

        $this->addListColumn('user_id', 'User ID');
        $this->addListColumn('strUserType', 'User type');
        $this->addListColumn('username', 'Username');
        $this->addListColumn('email', 'E-mail');
        $this->addListColumn('strFullName', 'Name');
        $this->addListColumn('active', 'Active');
    }

    public function initEdit()
    {
        parent::initEdit();

        $tab = new EditTab('default');

        $userTypeLf = new LFUserType();
        $userTypes = ListHelper::GetKeyValueList($userTypeLf->getList(null, array('user_type_id' => 'ASC')), 'user_type_id', 'caption');
        //unset($userTypeLf);
        //var_dump($userTypeLf->getList(), $userTypes);

        $tab->addField(new Input('username', null, 'text', null, 'username'), 'Username');
        $tab->addField(new Input('email', null, 'text', null, 'email'), 'E-mail', null, false);
        $tab->addField(new Input('first_name', null, 'text', null, 'first_name'), 'First name');
        $tab->addField(new Input('last_name', null, 'text', null, 'last_name'), 'Last name', null, false);
        $tab->addField(new Input('password', null, 'password', null, 'password'), 'Password');
        $tab->addField(new Input('password2', null, 'password', null, 'password2'), 'Confirm password', null, false);
        $tab->addField(new Textarea('address', null, null, 'address'), 'Address', null, true, 2, 1);
        $tab->addField(new Select('user_type_id', null, $userTypes, null, 'user_type_id'), 'User Type');
        $tab->addField(new Input('active', null, 'checkbox', null, 'active'), 'Active', null, true);

        $this->editTabs[] = $tab;
    }

    protected function initListFilters()
    {
        $this->addListFilter(new Input('f_user_id', null, null, 'input_small', 'user_id'), 'ID');
    }

    public function processList(&$list)
    {
        $userType = new TGUserType();
        foreach ($list as &$item) {
            try {
                $userType->load($item['user_type_id']);
                $item['strUserType'] = $userType->caption;
            } catch (Exception $e) {
                $item['strUserType'] = '[unknown]';
            }
            $item['strFullName'] = htmlspecialchars($item['first_name'] . ' ' . $item['last_name']);
        }
    }
}