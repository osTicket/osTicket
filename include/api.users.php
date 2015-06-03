<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.user.php';

class UsersApiController extends ApiController {

    public function create($format) {
		
        $key = $this->requireApiKey();

        if(!$key)
            return $this->exerr(401, __('API key not authorized'));

        $user = null;
        if(!strcasecmp($format, 'email')) {
             return $this->exerr(500, __('Email not supported at the moment'));
        } else {
            # Parse request body
            $user = $this->createUser($this->getRequest($format));
        }

        if(!$user)
            return $this->exerr(500, __('Unable to create new user: unknown error'));

        $this->response(201, $user->getId());
    }

    private function createUser($data) {
		$user = User::fromVars($data);
		return $user;
    }
	
}
