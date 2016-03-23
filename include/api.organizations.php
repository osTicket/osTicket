<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.organization.php';

class OrganizationsApiController extends ApiController {

    public function create($format) {
		
		$key = $this->requireApiKey();

        if(!$key)
            return $this->exerr(401, __('API key not authorized'));

        $organization = null;
        if(!strcasecmp($format, 'email')) {
             return $this->exerr(500, __('Email not supported at the moment'));
        } else {
            # Parse request body
            $organization = $this->createOrganization($this->getRequest($format));
        }

        if(!$organization)
            return $this->exerr(500, __('Unable to create new organization: unknown error'));

        $this->response(201, $organization->getId());
    }

    private function createOrganization($data) {
		$organization = Organization::fromVars($data, true);
		return $organization;
    }
	
}
