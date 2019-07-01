<?php
/*********************************************************************
    class.nav.php

    Navigation helper classes. Pointless BUT helps keep navigation clean and free from errors.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once(INCLUDE_DIR.'class.app.php');

class StaffNav {

    var $activetab;
    var $activeMenu;
    var $panel;
    var $subnavinfo;

    var $staff;

    function __construct($staff, $panel='staff'){
        $this->staff=$staff;
        $this->panel=strtolower($panel);
    }

    function __get($what) {
        // Lazily initialize the tabbing system
        switch($what) {
        case 'tabs':
            $this->tabs=$this->getTabs();
            break;
        case 'submenus':
            $this->submenus=$this->getSubMenus();
            break;
        default:
            throw new Exception($what . ': No such attribute');
        }
        return $this->{$what};
    }

    function getPanel(){
        return $this->panel;
    }

    function isAdminPanel(){
        return (!strcasecmp($this->getPanel(),'admin'));
    }

    function isStaffPanel() {
        return (!$this->isAdminPanel());
    }

    function getRegisteredApps() {
        return Application::getStaffApps();
    }

    function setTabActive($tab, $menu=''){

        if($this->tabs[$tab]){
            $this->tabs[$tab]['active']=true;
            if($this->activetab && $this->activetab!=$tab && $this->tabs[$this->activetab])
                 $this->tabs[$this->activetab]['active']=false;

            $this->activetab=$tab;
            if($menu) $this->setActiveSubMenu($menu, $tab);

            return true;
        }

        return false;
    }

    function setActiveTab($tab, $menu=''){
        return $this->setTabActive($tab, $menu);
    }

    function getActiveTab(){
        return $this->activetab;
    }

    function setActiveSubMenu($mid, $tab='') {
        if(is_numeric($mid))
            $this->activeMenu = $mid;
        elseif($mid && $tab && ($subNav=$this->getSubNav($tab))) {
            foreach($subNav as $k => $menu) {
                if(strcasecmp($mid, $menu['href'])) continue;

                $this->activeMenu = $k+1;
                break;
            }
        }
    }

    function getActiveMenu() {
        return $this->activeMenu;
    }

    function addSubMenu($item,$active=false){

        // Triger lazy loading if submenus haven't been initialized
        isset($this->submenus[$this->getPanel().'.'.$this->activetab]);
        $this->submenus[$this->getPanel().'.'.$this->activetab][]=$item;
        if($active)
            $this->activeMenu=sizeof($this->submenus[$this->getPanel().'.'.$this->activetab]);
    }

    function addSubNavInfo($classes=null, $id=null) {
        $T = $this->subnavinfo;
        $this->subnavinfo = array(
            'classes' => (@$T['classes'] ?: '') . ($classes ? " $classes" : ''),
            'id' => $id ?: @$T['id'],
        );
    }

    function getSubNavInfo() {
        return $this->subnavinfo;
    }

    function getTabs(){
        global $thisstaff;

        if(!$this->tabs) {
            $this->tabs = array();
            $this->tabs['dashboard'] = array(
                'desc'=>__('Dashboard'),'href'=>'dashboard.php','title'=>__('Agent Dashboard'), "class"=>"no-pjax"
            );
            if ($thisstaff->hasPerm(User::PERM_DIRECTORY)) {
                $this->tabs['users'] = array(
                    'desc' => __('Users'), 'href' => 'users.php', 'title' => __('User Directory')
                );
            }
            $this->tabs['tasks'] = array('desc'=>__('Tasks'), 'href'=>'tasks.php', 'title'=>__('Task Queue'));
            $this->tabs['tickets'] = array('desc'=>__('Tickets'),'href'=>'tickets.php','title'=>__('Ticket Queue'));

            $this->tabs['kbase'] = array('desc'=>__('Knowledgebase'),'href'=>'kb.php','title'=>__('Knowledgebase'));
            if (!is_null($this->getRegisteredApps()))
                $this->tabs['apps']=array('desc'=>__('Applications'),'href'=>'apps.php','title'=>__('Applications'));
        }

        return $this->tabs;
    }

    function getSubMenus(){ //Private.
        global $cfg;

        $staff = $this->staff;
        $submenus=array();
        foreach($this->getTabs() as $k=>$tab){
            $subnav=array();
            switch(strtolower($k)){
                case 'tasks':
                    $subnav[]=array('desc'=>__('Tasks'), 'href'=>'tasks.php', 'iconclass'=>'Ticket', 'droponly'=>true);
                    break;
                case 'dashboard':
                    $subnav[]=array('desc'=>__('Dashboard'),'href'=>'dashboard.php','iconclass'=>'logs');
                    $subnav[]=array('desc'=>__('Agent Directory'),'href'=>'directory.php','iconclass'=>'teams');
                    $subnav[]=array('desc'=>__('My Profile'),'href'=>'profile.php','iconclass'=>'users');
                    break;
                case 'users':
                    $subnav[] = array('desc' => __('User Directory'), 'href' => 'users.php', 'iconclass' => 'teams');
                    $subnav[] = array('desc' => __('Organizations'), 'href' => 'orgs.php', 'iconclass' => 'departments');
                    break;
                case 'kbase':
                    $subnav[]=array('desc'=>__('FAQs'),'href'=>'kb.php', 'urls'=>array('faq.php'), 'iconclass'=>'kb');
                    if($staff) {
                        if ($staff->hasPerm(FAQ::PERM_MANAGE))
                            $subnav[]=array('desc'=>__('Categories'),'href'=>'categories.php','iconclass'=>'faq-categories');
                        if ($cfg->isCannedResponseEnabled() && $staff->hasPerm(Canned::PERM_MANAGE, false))
                            $subnav[]=array('desc'=>__('Canned Responses'),'href'=>'canned.php','iconclass'=>'canned');
                    }
                   break;
                case 'apps':
                    foreach ($this->getRegisteredApps() as $app)
                        $subnav[] = $app;
                    break;
            }
            if($subnav)
                $submenus[$this->getPanel().'.'.strtolower($k)]=$subnav;
        }

        return $submenus;
    }

    function getSubMenu($tab=null){
        $tab=$tab?$tab:$this->activetab;
        return $this->submenus[$this->getPanel().'.'.$tab];
    }

    function getSubNav($tab=null){
        return $this->getSubMenu($tab);
    }

}

class AdminNav extends StaffNav{

    function __construct($staff){
        parent::__construct($staff, 'admin');
    }

    function getRegisteredApps() {
        return Application::getAdminApps();
    }

    function getTabs(){

        if(!$this->tabs){

            $tabs=array();
            $tabs['dashboard']=array('desc'=>__('Dashboard'),'href'=>'logs.php','title'=>__('Admin Dashboard'));
            $tabs['settings']=array('desc'=>__('Settings'),'href'=>'settings.php','title'=>__('System Settings'));
            $tabs['manage']=array('desc'=>__('Manage'),'href'=>'helptopics.php','title'=>__('Manage Options'));
            $tabs['emails']=array('desc'=>__('Emails'),'href'=>'emails.php','title'=>__('Email Settings'));
            $tabs['staff']=array('desc'=>__('Agents'),'href'=>'staff.php','title'=>__('Manage Agents'));
            if (!is_null($this->getRegisteredApps()))
                $tabs['apps']=array('desc'=>__('Applications'),'href'=>'apps.php','title'=>__('Applications'));
            $this->tabs=$tabs;
        }

        return $this->tabs;
    }

    function getSubMenus(){

        $submenus=array();
        foreach($this->getTabs() as $k=>$tab){
            $subnav=array();
            switch(strtolower($k)){
                case 'dashboard':
                    $subnav[]=array('desc'=>__('System Logs'),'href'=>'logs.php','iconclass'=>'logs');
                    if (PluginManager::auditPlugin())
                        $subnav[]=array('desc'=>__('Audit Logs'),'href'=>'audits.php','iconclass'=>'lists');
                    $subnav[]=array('desc'=>__('Information'),'href'=>'system.php','iconclass'=>'preferences');
                    break;
                case 'settings':
                    $subnav[]=array('desc'=>__('Company'),'href'=>'settings.php?t=pages','iconclass'=>'pages');
                    $subnav[]=array('desc'=>__('System'),'href'=>'settings.php?t=system','iconclass'=>'preferences');
                    $subnav[]=array('desc'=>__('Tickets'),'href'=>'settings.php?t=tickets','iconclass'=>'ticket-settings');
                    $subnav[]=array('desc'=>__('Tasks'),'href'=>'settings.php?t=tasks','iconclass'=>'lists');
                    $subnav[]=array('desc'=>__('Agents'),'href'=>'settings.php?t=agents','iconclass'=>'teams');
                    $subnav[]=array('desc'=>__('Users'),'href'=>'settings.php?t=users','iconclass'=>'groups');
                    $subnav[]=array('desc'=>__('Knowledgebase'),'href'=>'settings.php?t=kb','iconclass'=>'kb-settings');
                    break;
                case 'manage':
                    $subnav[]=array('desc'=>__('Help Topics'),'href'=>'helptopics.php','iconclass'=>'helpTopics');
                    $subnav[]=array('desc'=>__('Filters'),'href'=>'filters.php',
                                        'title'=>__('Ticket Filters'),'iconclass'=>'ticketFilters');
                    $subnav[]=array('desc'=>__('SLA'),'href'=>'slas.php','iconclass'=>'sla');
                    $subnav[]=array('desc'=>__('Schedules'),'href'=>'schedules.php','iconclass'=>'lists');
                    $subnav[]=array('desc'=>__('API'),'href'=>'apikeys.php','iconclass'=>'api');
                    $subnav[]=array('desc'=>__('Pages'), 'href'=>'pages.php','title'=>'Pages','iconclass'=>'pages');
                    $subnav[]=array('desc'=>__('Forms'),'href'=>'forms.php','iconclass'=>'forms');
                    $subnav[]=array('desc'=>__('Lists'),'href'=>'lists.php','iconclass'=>'lists');
                    $subnav[]=array('desc'=>__('Plugins'),'href'=>'plugins.php','iconclass'=>'api');
                    break;
                case 'emails':
                    $subnav[]=array('desc'=>__('Emails'),'href'=>'emails.php', 'title'=>__('Email Addresses'), 'iconclass'=>'emailSettings');
                    $subnav[]=array('desc'=>__('Settings'),'href'=>'emailsettings.php','iconclass'=>'email-settings');
                    $subnav[]=array('desc'=>__('Banlist'),'href'=>'banlist.php',
                                        'title'=>__('Banned Emails'),'iconclass'=>'emailDiagnostic');
                    $subnav[]=array('desc'=>__('Templates'),'href'=>'templates.php','title'=>__('Email Templates'),'iconclass'=>'emailTemplates');
                    $subnav[]=array('desc'=>__('Diagnostic'),'href'=>'emailtest.php', 'title'=>__('Email Diagnostic'), 'iconclass'=>'emailDiagnostic');
                    break;
                case 'staff':
                    $subnav[]=array('desc'=>__('Agents'),'href'=>'staff.php','iconclass'=>'users');
                    $subnav[]=array('desc'=>__('Teams'),'href'=>'teams.php','iconclass'=>'teams');
                    $subnav[]=array('desc'=>__('Roles'),'href'=>'roles.php','iconclass'=>'lists');
                    $subnav[]=array('desc'=>__('Departments'),'href'=>'departments.php','iconclass'=>'departments');
                    break;
                case 'apps':
                    foreach ($this->getRegisteredApps() as $app)
                        $subnav[] = $app;
                    break;
            }
            if($subnav)
                $submenus[$this->getPanel().'.'.strtolower($k)]=$subnav;
        }

        return $submenus;
    }
}

class UserNav {

    var $navs=array();
    var $activenav;

    var $user;

    function __construct($user=null, $active=''){

        $this->user=$user;
        $this->navs=$this->getNavs();
        if($active)
            $this->setActiveNav($active);
    }

    function getRegisteredApps() {
        return Application::getClientApps();
    }

    function setActiveNav($nav){

        if($nav && $this->navs[$nav]){
            $this->navs[$nav]['active']=true;
            if($this->activenav && $this->activenav!=$nav && $this->navs[$this->activenav])
                 $this->navs[$this->activenav]['active']=false;

            $this->activenav=$nav;

            return true;
        }

        return false;
    }

    function getNavLinks(){
        global $cfg;

        //Paths are based on the root dir.
        if(!$this->navs){

            $navs = array();
            $user = $this->user;
            $navs['home']=array('desc'=>__('Support Center Home'),'href'=>'index.php','title'=>'');
            if($cfg && $cfg->isKnowledgebaseEnabled())
                $navs['kb']=array('desc'=>__('Knowledgebase'),'href'=>'kb/index.php','title'=>'');

            // Show the "Open New Ticket" link unless BOTH client
            // registration is disabled and client login is required for new
            // tickets. In such a case, creating a ticket would not be
            // possible for web clients.
            if ($cfg->getClientRegistrationMode() != 'disabled'
                    || !$cfg->isClientLoginRequired())
                $navs['new']=array('desc'=>__('Open a New Ticket'),'href'=>'open.php','title'=>'');
            if($user && $user->isValid()) {
                if(!$user->isGuest()) {
                    $navs['tickets']=array('desc'=>sprintf(__('Tickets (%d)'),$user->getNumTickets($user->canSeeOrgTickets())),
                                           'href'=>'tickets.php',
                                            'title'=>__('Show all tickets'));
                } else {
                    $navs['tickets']=array('desc'=>__('View Ticket Thread'),
                                           'href'=>sprintf('tickets.php?id=%d',$user->getTicketId()),
                                           'title'=>__('View ticket status'));
                }
            } else {
                $navs['status']=array('desc'=>__('Check Ticket Status'),'href'=>'view.php','title'=>'');
            }
            $this->navs=$navs;
        }

        return $this->navs;
    }

    function getNavs(){
        return $this->getNavLinks();
    }

}

?>
