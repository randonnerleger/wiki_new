<?php
/**
 * FluxBB auth backend
 *
 * Uses external Trust mechanism to check against FluxBB's
 * user cookie. FluxBB's PUN_ROOT must be defined correctly.
 *
 * @author    Andreas Gohr <andi@splitbrain.org>
 */
#dbg($GLOBALS);
#dbg($forum_user);

class auth_plugin_authfluxbb extends DokuWiki_Auth_Plugin {

  /**
   * Constructor.
   *
   * Sets additional capabilities and config strings
   */
  public function __construct() {
        parent::__construct();
    global $conf;
    $this->cando['external'] = true;

    $conf['passcrypt'] = 'sha1';

    // get global vars from fluxbb config
    global $db_host;
    global $db_name;
    global $db_username;
    global $db_password;
    global $db_prefix;

    // now set up the mysql config strings
    $conf['auth']['mysql']['server']   = $db_host;
    $conf['auth']['mysql']['user']     = $db_username;
    $conf['auth']['mysql']['password'] = $db_password;
    $conf['auth']['mysql']['database'] = $db_name;

    $conf['auth']['mysql']['checkPass']   = "SELECT u.password AS pass
                                               FROM ${db_prefix}users AS u, ${db_prefix}groups AS g
                                              WHERE u.group_id = g.g_id
                                                AND u.username = '%{user}'
                                                AND g.g_title   != 'Guest'";
    $conf['auth']['mysql']['getUserInfo'] = "SELECT password AS pass, realname AS name, email AS mail,
                                                    id, g_title as `group`
                                               FROM ${db_prefix}users AS u, ${db_prefix}groups AS g
                                              WHERE u.group_id = g.g_id
                                                AND u.username = '%{user}'";
    $conf['auth']['mysql']['getGroups']   = "SELECT g.g_title as `group`
                                               FROM ${db_prefix}users AS u, ${db_prefix}groups AS g
                                              WHERE u.group_id = g.g_id
                                                AND u.username = '%{user}'";
    $conf['auth']['mysql']['getUsers']    = "SELECT DISTINCT u.username AS user
                                               FROM ${db_prefix}users AS u, ${db_prefix}groups AS g
                                              WHERE u.group_id = g.g_id";
    $conf['auth']['mysql']['FilterLogin'] = "u.username LIKE '%{user}'";
    $conf['auth']['mysql']['FilterName']  = "u.realname LIKE '%{name}'";
    $conf['auth']['mysql']['FilterEmail'] = "u.email    LIKE '%{email}'";
    $conf['auth']['mysql']['FilterGroup'] = "g.g_title    LIKE '%{group}'";
    $conf['auth']['mysql']['SortOrder']   = "ORDER BY u.username";
    $conf['auth']['mysql']['addUser']     = "INSERT INTO ${db_prefix}users
                                                    (username, password, email, realname)
                                             VALUES ('%{user}', '%{pass}', '%{email}', '%{name}')";
    $conf['auth']['mysql']['addGroup']    = "INSERT INTO ${db_prefix}groups (g_title) VALUES ('%{group}')";
    $conf['auth']['mysql']['addUserGroup']= "UPDATE ${db_prefix}users
                                                SET group_id=%{gid}
                                              WHERE id='%{uid}'";
    $conf['auth']['mysql']['delGroup']    = "DELETE FROM ${db_prefix}groups WHERE g_id='%{gid}'";
    $conf['auth']['mysql']['getUserID']   = "SELECT id FROM ${db_prefix}users WHERE username='%{user}'";
    $conf['auth']['mysql']['updateUser']  = "UPDATE ${db_prefix}users SET";
    $conf['auth']['mysql']['UpdateLogin'] = "username='%{user}'";
    $conf['auth']['mysql']['UpdatePass']  = "password='%{pass}'";
    $conf['auth']['mysql']['UpdateEmail'] = "email='%{email}'";
    $conf['auth']['mysql']['UpdateName']  = "realname='%{name}'";
    $conf['auth']['mysql']['UpdateTarget']= "WHERE id=%{uid}";
    $conf['auth']['mysql']['delUserGroup']= "UPDATE ${db_prefix}users SET g_id=4 WHERE id=%{uid}";
    $conf['auth']['mysql']['getGroupID']  = "SELECT g_id AS id FROM ${db_prefix}groups WHERE g_title='%{group}'";

    $conf['auth']['mysql']['TablesToLock']= array("${db_prefix}users", "${db_prefix}users AS u",
                                                  "${db_prefix}groups", "${db_prefix}groups AS g");

    $conf['auth']['mysql']['debug'] = 1;
    // call mysql constructor
    parent::__construct();
  }

  /**
   * Just checks against the $forum_user variable
   */
  function trustExternal($user,$pass,$sticky=false){
    global $USERINFO;
    global $conf;
    global $lang;
    global $pun_user;
    global $pun_config;
    global $cookie_name;
    $sticky ? $sticky = true : $sticky = false; //sanity check

    if(isset($pun_user) && !$pun_user['is_guest']){
      // okay we're logged in - set the globals
      $USERINFO['pass'] = $pun_user['password'];
      $USERINFO['name'] = utf8_encode($pun_user['realname']);
      $USERINFO['mail'] = $pun_user['email'];
      $USERINFO['grps'] = array($pun_user['g_title']);
      if ($pun_user['is_admmod'])
        $USERINFO['grps'][] = 'admin';

      $_SERVER['REMOTE_USER'] = utf8_decode($pun_user['username']);
      $_SESSION[DOKU_COOKIE]['auth']['user'] = $pun_user['username'];
      $_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;
      return true;
    }

    // to be sure
    auth_logoff();

    $USERINFO['grps'] = array();
    return false;
  }
}
//Setup VIM: ex: et ts=2 enc=utf-8 :