<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SetupController
{
    /**
     * @param Request          $request
     * @param Twig_Environment $twig
     * @param Installer        $installer
     *
     * @return Response
     */
    public function startAction(Request $request, \Twig_Environment $twig, Installer $installer)
    {
        //Fail IF any of the old config files exists.
        if ($this->doesOldConfigExist()) {
            $twigOutput = $twig->render('setup/fileUnclean.html.twig');

            return Response::create($twigOutput);
        }

        $error = null;
        if ($request->getMethod() === 'POST') {
            if ($installer->check_prereq()) {
                $_SESSION['ost_installer']['s'] = 'config';

                return $this->configAction($request, $twig, $installer);
            } else {
                $error = __('Minimum requirements not met!');
            }
        }

        $requiredExtensions = [
            'php'   => $installer->check_php() ? 'yes' : 'no',
            'mysql' => $installer->check_mysql() ? 'yes' : 'no',
        ];
        $optionalExtensions = [
            'gd'          => (extension_loaded('gd') ? 'yes' : 'no'),
            'imap'        => (extension_loaded('imap') ? 'yes' : 'no'),
            'xml'         => (extension_loaded('xml') ? 'yes' : 'no'),
            'dom'         => (extension_loaded('dom') ? 'yes' : 'no'),
            'json'        => (extension_loaded('json') ? 'yes' : 'no'),
            'mbstring'    => (extension_loaded('mbstring') ? 'yes' : 'no'),
            'phar'        => (extension_loaded('phar') ? 'yes' : 'no'),
            'intl'        => (extension_loaded('intl') ? 'yes' : 'no'),
            'apcu'        => (extension_loaded('apcu') ? 'yes' : 'no'),
            'zendOpcache' => (extension_loaded('Zend OPcache') ? 'yes' : 'no'),
        ];

        $installPreRequiredParameters = [
            'phpVersion'         => PHP_VERSION,
            'requiredExtensions' => $requiredExtensions,
            'optionalExtensions' => $optionalExtensions
        ];

        if ($error) {
            $installPreRequiredParameters['error'] = $error;
        }

        $twigOutput = $twig->render('setup/installPreRequired.html.twig', $installPreRequiredParameters);

        return Response::create($twigOutput);
    }

    /**
     * @return bool
     */
    private function doesOldConfigExist()
    {
        return
            file_exists(INCLUDE_DIR . 'settings.php') ||
            file_exists(ROOT_DIR . 'ostconfig.php') ||
            (
                file_exists(OSTICKET_CONFIGFILE) &&
                preg_match("/define\('OSTINSTALLED',TRUE\)\;/i", file_get_contents(OSTICKET_CONFIGFILE))
            );
    }

    /**
     * @param Request          $request
     * @param Twig_Environment $twig
     * @param Installer        $installer
     *
     * @return Response
     */
    public function configAction(Request $request, \Twig_Environment $twig, Installer $installer)
    {
        if (!$installer->config_exists()) {
            $fileMissingParameters = ['error' => (isset($errors['err']) ? $errors['err'] : null)];

            $twigOutput = $twig->render('setup/fileMissing.html.twig', $fileMissingParameters);

            return Response::create($twigOutput);
        }

        if (!($cFile = file_get_contents($installer->getConfigFile())) || preg_match(
                "/define\('OSTINSTALLED',TRUE\)\;/i",
                $cFile
            )
        ) {
            //osTicket already installed or empty config file?
            $twigOutput = $twig->render('setup/fileUnclean.html.twig');

            return Response::create($twigOutput);
        }

        if (!$installer->config_writable()) {
            //writable config file??
            clearstatcache();
            $filePermissionsParameters = ['error' => (isset($errors['err']) ? $errors['err'] : null)];

            $twigOutput = $twig->render('setup/filePermissions.html.twig', $filePermissionsParameters);

            return Response::create($twigOutput);
        }

        $info = ['prefix' => 'ost_', 'dbhost' => 'localhost', 'lang_id' => 'en_US'];
        if ($request->getMethod() === 'POST') {
            $info = $request->request->all();

            if (!$installer->config_exists()) {
                $errors['err'] = __('Configuration file does NOT exist. Follow steps below to add one.');
            } elseif (!$installer->config_writable()) {
                $errors['err'] = __('Write access required to continue');
            } else {
                $_SESSION['ost_installer']['s'] = 'install';

                return $this->installAction($request, $twig, $installer);
            }
        }

        $availableLanguages = [];
        foreach (Internationalization::availableLanguages() as $language) {
            $availableLanguages[$language['code']] = Internationalization::getLanguageDescription(
                $language['code']
            );
        }

        $installParameters = [
            'error'              => (isset($errors['err']) ? $errors['err'] : null),
            'errors'             => $errors,
            'url'                => URL,
            'info'               => $info,
            'availableLanguages' => $availableLanguages
        ];
        $twigOutput        = $twig->render('setup/install.html.twig', $installParameters);

        return Response::create($twigOutput);
    }

    /**
     * @param Request          $request
     * @param Twig_Environment $twig
     * @param Installer        $installer
     *
     * @return Response
     */
    public function installAction(Request $request, \Twig_Environment $twig, Installer $installer)
    {
        if (!$installer->config_exists()) {
            $fileMissingParameters = ['error' => (isset($errors['err']) ? $errors['err'] : null)];
            $twigOutput            = $twig->render('setup/fileMissing.html.twig', $fileMissingParameters);

            return Response::create($twigOutput);
        }
        if (
            !($cFile = file_get_contents($installer->getConfigFile())) ||
            preg_match("/define\('OSTINSTALLED',TRUE\)\;/i", $cFile)
        ) {
            //osTicket already installed or empty config file?
            $twigOutput = $twig->render('setup/fileUnclean.html.twig');

            return Response::create($twigOutput);
        }
        if (!$installer->config_writable()) {
            //writable config file??
            clearstatcache();
            $filePermissionsParameters = ['error' => (isset($errors['err']) ? $errors['err'] : null)];

            $twigOutput = $twig->render('setup/filePermissions.html.twig', $filePermissionsParameters);

            return Response::create($twigOutput);
        }

        $info = ['prefix' => 'ost_', 'dbhost' => 'localhost', 'lang_id' => 'en_US'];
        if ($request->getMethod() === 'POST') {
            $info = $request->request->all();

            if ($installer->install($_POST)) {
                $_SESSION['info'] = [
                    'name'  => ucfirst($info['fname'] . ' ' . $info['lname']),
                    'email' => $info['admin_email'],
                    'URL'   => URL
                ];

                //TODO: Go to subscribe step.
                $_SESSION['ost_installer']['s'] = 'done';

                return $this->subscribeAction($request, $twig, $installer);
            } elseif (!($errors = $installer->getErrors()) || !$errors['err']) {
                $errors['err'] = sprintf(
                    '%s %s',
                    __('Error installing osTicket.'),
                    __('Correct any errors below and try again.')
                );
            }
        }

        $availableLanguages = [];
        foreach (Internationalization::availableLanguages() as $language) {
            $availableLanguages[$language['code']] = Internationalization::getLanguageDescription(
                $language['code']
            );
        }

        $installParameters = [
            'error'              => (isset($errors['err']) ? $errors['err'] : null),
            'errors'             => $errors,
            'url'                => URL,
            'info'               => $info,
            'availableLanguages' => $availableLanguages
        ];
        $twigOutput        = $twig->render('setup/install.html.twig', $installParameters);

        return Response::create($twigOutput);
    }

    /**
     * @param Request          $request
     * @param Twig_Environment $twig
     * @param Installer        $installer
     *
     * @return Response
     */
    public function subscribeAction(Request $request, \Twig_Environment $twig, Installer $installer)
    {
        $info   = $_SESSION['info'];
        $errors = null;
        if ($request->getMethod() === 'POST') {
            $info = $request->request->all();

            if (!trim($_POST['name'])) {
                $errors['name'] = __('Required');
            }

            if (!$_POST['email']) {
                $errors['email'] = __('Required');
            } elseif (!Validator::is_valid_email($_POST['email'])) {
                $errors['email'] = __('Invalid');
            }

            if (!$_POST['alerts'] && !$_POST['news']) {
                $errors['notify'] = __('Check one or more');
            }

            if (!$errors) {
                $_SESSION['ost_installer']['s'] = 'done';
                return $this->doneAction($twig, $installer);
            }
        }

        $subscribeParameters = ['info' => $info];
        if ($errors) {
            $subscribeParameters['errors'] = $errors;
        }

        $twigOutput = $twig->render('setup/subscribe.html.twig', $subscribeParameters);

        return Response::create($twigOutput);
    }

    /**
     * @param Twig_Environment $twig
     * @param Installer        $installer
     *
     * @return Response
     */
    public function doneAction(\Twig_Environment $twig, Installer $installer)
    {
        if (!$installer->config_exists()) {
            $requiredExtensions = [
                'php'   => $installer->check_php() ? 'yes' : 'no',
                'mysql' => $installer->check_mysql() ? 'yes' : 'no',
            ];
            $optionalExtensions = [
                'gd'          => (extension_loaded('gd') ? 'yes' : 'no'),
                'imap'        => (extension_loaded('imap') ? 'yes' : 'no'),
                'xml'         => (extension_loaded('xml') ? 'yes' : 'no'),
                'dom'         => (extension_loaded('dom') ? 'yes' : 'no'),
                'json'        => (extension_loaded('json') ? 'yes' : 'no'),
                'mbstring'    => (extension_loaded('mbstring') ? 'yes' : 'no'),
                'phar'        => (extension_loaded('phar') ? 'yes' : 'no'),
                'intl'        => (extension_loaded('intl') ? 'yes' : 'no'),
                'apcu'        => (extension_loaded('apcu') ? 'yes' : 'no'),
                'zendOpcache' => (extension_loaded('Zend OPcache') ? 'yes' : 'no'),
            ];

            $installPreRequiredParameters = [
                'phpVersion'         => PHP_VERSION,
                'requiredExtensions' => $requiredExtensions,
                'optionalExtensions' => $optionalExtensions
            ];
            $twigOutput                   = $twig->render(
                'setup/installPreRequired.html.twig',
                $installPreRequiredParameters
            );

            return Response::create($twigOutput);
        }

        // Clear installer session
        $_SESSION['ost_installer'] = [];

        $parameters = ['url' => URL];
        $twigOutput = $twig->render('setup/installDone.html.twig', $parameters);

        return Response::create($twigOutput);
    }
}
