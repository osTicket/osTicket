<?php
include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.organization.php';
class OrganizationApiController extends ApiController {

    # Copied from TicketApiController.  Not fully implemented
    function getRequestStructure($format, $data=null) {
        return ['name','address','phone','website','notes'];
    }
    # Copied from TicketApiController.  Not implemented
    function validate(&$data, $format, $strict=true) {
        //Add as applicable.
        return true;
    }

    public function get($format, $oid) {
        if(!($key=$this->requireApiKey()) || !$key->canViewUser())
            return $this->exerr(401, __('API key not authorized'));
        if(!$org = Organization::lookup($oid))
            return $this->exerr(400, __("Organization ID '$oid' does not exist"));
        Http::response(200, $org->to_json(), 'application/json');
        //$this->response(200, json_encode($org->getOrganizationApiEntity()));
    }

    public function create($format) {
        if(!($key=$this->requireApiKey()) || !$key->canAddOrganization())
            return $this->exerr(401, __('API key not authorized'));

        $params = $this->getRequest($format);
        $params=array_merge(array_fill_keys(['address','phone','website','notes'],null), $params);  //Optional properties
        $params=array_intersect_key($params, array_flip($this->getRequestStructure($format)));      //Strip off unused properties
        if ($missing=array_diff($this->getRequestStructure($format), array_keys($params))) {
            return $this->exerr(400, __('Missing parameters '.implode(', ', $missing)));
        }
        if(Organization::lookup(['name'=>$params['name']])) {
            return $this->exerr(400, __("Organization name '$params[name]' is already in use"));
        }

        if(!$org=Organization::fromVars($params)) {
            return $this->exerr(400, __('Unknown organization creation error'));
        }
        Http::response(201, $org->to_json(), 'application/json');
        //$this->response(201, json_encode($org->getOrgApiEntity()));
    }

    public function delete($format, $oid) {
        if(!($key=$this->requireApiKey()) || !$key->canDeleteOrganization())
            return $this->exerr(401, __('API key not authorized'));
        // Organization::objects()->filter(['id__in' => [$oid]])
        if(!$org = Organization::lookup($oid))
            return $this->exerr(422, __("Organization ID '$oid' does not exist"));
        if(!empty($_GET['deleteUsers'])) {  //delete users before deleting organization
            foreach ($org->allMembers() as $user) {
                $user->delete();
            }
        }
        if(!$org->delete()){
            return $this->exerr(500, __('Error deleting organization'));
        }
        $this->response(204, null);
    }

    public function getUsers($format, $oid) {
        if(!($key=$this->requireApiKey()) || !$key->canViewOrganization())
            return $this->exerr(401, __('API key not authorized'));
        if(!$org = Organization::lookup($oid))
            return $this->exerr(400, __("Organization ID '$oid' does not exist"));
        foreach ($org->allMembers() as $user) {
            $users[]=$user->getUserApiEntity();
        }
        $this->response(200, json_encode($users));
    }
}