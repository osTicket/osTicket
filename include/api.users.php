<?php
include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.user.php';
class UserApiController extends ApiController {

    # Copied from TicketApiController.  Not fully implemented
    function getRequestStructure($format, $data=null) {
        return ["email", "name", "org_id", "phone", "notes", "password", "timezone"];
    }
    # Copied from TicketApiController.  Not implemented
    function validate(&$data, $format, $strict=true) {
        //Add as applicable.
        return true;
    }

    public function get($format, $uid) {
        if(!($key=$this->requireApiKey()) || !$key->canViewUser())
            return $this->exerr(401, __('API key not authorized'));
        if(!$user = User::lookup($uid))
            return $this->exerr(400, __("User ID '$uid' does not exist"));
        //$this->response(200, $user->toJson());
        $this->response(200, json_encode($user->getUserApiEntity()));
    }

    public function create($format) {
        //see ajax.users.php addUser() and class.api.php for example
        if(!($key=$this->requireApiKey()) || !$key->canAddUser())
            return $this->exerr(401, __('API key not authorized'));
        $params = $this->getRequest($format);
        $params=array_merge(array_fill_keys(["org_id", "phone", "notes", "timezone"],null), $params);  //Optional properties
        $params=array_intersect_key($params, array_flip($this->getRequestStructure($format)));      //Strip off unused properties
        if ($missing=array_diff($this->getRequestStructure($format), array_keys($params))) {
            return $this->exerr(400, __('Missing parameters '.implode(', ', $missing)));
        }

        if (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->exerr(400, __("Invalid email: $params[email]"));
        }
        if(User::lookup(['emails__address'=>$params['email']])) {
            return $this->exerr(400, __("Email $params[email] is already in use"));
        }
        if(!$user=User::fromVars($params)) {
            return $this->exerr(400, __('Unknown user creation error'));
        }
        $errors=[];
        $params=array_merge($params,['username'=>$params['email'],'passwd1'=>$params['password'],'passwd2'=>$params['password'],'timezone'=>$params['timezone']]);
        if(!$user->register($params, $errors)) {
            return $this->exerr(400, __('User added but error attempting to register'));
        }
        $this->response(201, json_encode($user->getUserApiEntity()));
        //$this->response(201, $user->to_json());
    }

    public function delete($format, $uid) {
        if(!($key=$this->requireApiKey()) || !$key->canDeleteUser())
            return $this->exerr(401, __('API key not authorized'));
        if(!$user = User::lookup($uid))
            return $this->exerr(400, __("User ID '$uid' does not exist"));
        $user->deleteAllTickets();
        if(!$user->delete()){
            return $this->exerr(500, __('Error deleting user'));
        }
        $this->response(204, null);
    }
}