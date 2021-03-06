<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/
namespace TheliaStore\Controller\Admin;

use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Security\Token\TokenProvider;
use Thelia\Core\Translation\Translator;
use Thelia\Form\CustomerLogin;
use TheliaStore\Classes\TheliaStoreUser;
use TheliaStore\Form\StoreAccountCreationForm;
use TheliaStore\Form\StoreAccountUpdateForm;
use TheliaStore\Form\StoreAccountUpdatePasswordForm;
use TheliaStore\TheliaStore;

class StoreAccountController extends BaseAdminController
{

    use \Thelia\Tools\RememberMeTrait;

    public function defaultAction()
    {
        return $this->render('store-account');
    }

    public function addCurrentWebSite()
    {
        $session = new Session();
        $dataAccount = $session->get('storecustomer');

        $dataApi['customer_id'] = $dataAccount['ID'];
        $dataApi['domain'] = "http://".$_SERVER['SERVER_NAME'];
        $dataApi['local'] = $session->getLang()->getLocale();

        $client = TheliaStore::getApi();
        list($status, $data) = $client->doPost('customer/'.$dataApi['customer_id'].'/theliawebsite/create', $dataApi);

        if ($status == 201) {
            //return $this->render('store-account');
            $this->setCurrentRouter('router.TheliaStore');
            return $this->generateRedirectFromRoute(
                'theliastore.store',
                [],
                []

            );
        }
        $error = 'Désolé, une erreur est survenu';
        if (isset($data['error'])) {
            $error = $data['error'];
        }

        //return $this->render('store-account', ['error' => $error]);
        $this->setCurrentRouter('router.TheliaStore');
        return $this->generateRedirectFromRoute(
            'theliastore.store',
            [],
            []

        );
    }

    public function updateFormAction()
    {
        return $this->render('account-updateform');
    }

    public function updateAction()
    {
        try {
            $request = $this->getRequest();
            $myForm = new StoreAccountUpdateForm($request);

            $form = $this->validateForm($myForm);
            $myData = $form->getData();

            $client = TheliaStore::getApi();

            $dataApi['id'] = $myData['id'];
            $dataApi['email'] = $myData['email'];
            $dataApi['firstname'] = $myData['firstname'];
            $dataApi['lastname'] = $myData['lastname'];
            $dataApi['address1'] = $myData['address1'];
            $dataApi['address2'] = $myData['address2'];
            $dataApi['address3'] = $myData['address3'];
            $dataApi['zipcode'] = $myData['zipcode'];
            $dataApi['city'] = $myData['city'];
            $dataApi['country'] = $myData['country'];
            $dataApi['title'] = $myData['title'];
            $dataApi['lang_id'] = $myData['lang'];

            //var_dump($dataApi);

            list($status, $data) = $client->doPut('customers', $dataApi);
            //var_dump($status);
            //var_dump($data);

            $error = '';

            if ($status == 201 && is_array($data)) {
                if (isset($data[0]['ID']) && $data[0]['ID'] > 0) {
                    $session = new Session();
                    $session->set('storecustomer', $data[0]);
                }
            } else {
                $error = Translator::getInstance()->trans('CustomerUpdateActionError', [], TheliaStore::DOMAIN_NAME);
            }

            return $this->render('account-updateform', ['error' => $error]);
        } catch (\Exception $e) {
            return $this->render('account-updateform', ['error' => $e->getMessage()], 500);
        }

    }

    public function changePasswordFormAction()
    {
        return $this->render('account-changepasswordform');
    }

    public function changePasswordAction()
    {
        try {
            $request = $this->getRequest();
            $myForm = new StoreAccountUpdatePasswordForm($request);

            $form = $this->validateForm($myForm);
            $myData = $form->getData();

            $error = '';
            $success = '';

            if ($myData['password'] != $myData['password_confirm']) {
                $error = Translator::getInstance()->trans(
                    'CustomerChangePasswordActionError',
                    [],
                    TheliaStore::DOMAIN_NAME
                );
            } else {
                $client = TheliaStore::getApi();

                $dataApi['id'] = $myData['id'];
                $dataApi['email'] = $myData['email'];
                $dataApi['password'] = $myData['password'];

                list($status, $data) = $client->doPut('customers/' . $dataApi['id'] . '/changepassword', $dataApi);
                if ($status == 201) {
                    $success = Translator::getInstance()->trans(
                        'CustomerChangePasswordActionSuccess',
                        [],
                        TheliaStore::DOMAIN_NAME
                    );
                } else {
                    $error = Translator::getInstance()->trans(
                        'CustomerChangePasswordActionError',
                        [],
                        TheliaStore::DOMAIN_NAME
                    );
                }
            }

            return $this->render('account-changepasswordform', ['error' => $error, 'success' => $success]);
        } catch (\Exception $e) {
            return $this->render('account-changepasswordform', ['error' => $e->getMessage()], 500);
        }
    }

    public function createFormAction()
    {
        return $this->render('account-createform');
    }

    public function createAction()
    {

        $request = $this->getRequest();
        $myForm = new StoreAccountCreationForm($request);

        $form = $this->validateForm($myForm);
        $myData = $form->getData();

        $client = TheliaStore::getApi();

        $dataApi['email'] = $myData['email'];
        $dataApi['password'] = $myData['password'];
        $dataApi['firstname'] = $myData['firstname'];
        $dataApi['lastname'] = $myData['lastname'];
        $dataApi['address1'] = $myData['address1'];
        $dataApi['zipcode'] = $myData['zipcode'];
        $dataApi['city'] = $myData['city'];
        $dataApi['country'] = $myData['country'];
        $dataApi['title'] = $myData['title'];
        $dataApi['lang_id'] = $myData['lang'];

        $dataApi['token'] = $request->get('token');
        $dataApi['ip'] = $request->get('ip');
        $dataApi['userip'] = $_SERVER['REMOTE_ADDR'];

        //var_dump($dataApi);

        //list($status, $data) = $client->doPost('customers', $dataApi);
        list($status, $data) = $client->doPost('customers/account-creation', $dataApi);

        //var_dump($status);
        //var_dump($data);

        if ($status == 201) {
            if (isset($data[0]['ID']) && $data[0]['ID'] > 0) {
                $session = new Session();
                $session->set('isconnected', '1');
                $session->set('storecustomer', $data[0]);

                $this->setCurrentRouter('router.TheliaStore');
                //return $this->render('account-createform');

                return $this->generateRedirectFromRoute(
                    'theliastore.store',
                    [],
                    []

                );

            }
        }
        $error = 'Désolé, une erreur est survenu';
        if (isset($data['error'])) {
            $error = $data['error'];
        }

        return $this->render('account-createform', ['error' => $error]);
    }

    public function loginAction()
    {

        $request = $this->getRequest();
        $myForm = new CustomerLogin($request);

        $form = $this->validateForm($myForm);
        $myData = $form->getData();

        $client = TheliaStore::getApi();
        list($status, $data) = $client->doPost(
            "customers/checkLogin",
            ["email" => $myData['email'], "password" => $myData['password']]
        );

        //var_dump($status);
        //var_dump($data);
        /*
         array (size=1)
          0 =>
            array (size=14)
              'ID' => int 1
              'REF' => string 'CUS000000000001' (length=15)
              'TITLE' => int 1
              'FIRSTNAME' => string 'jvigouroux' (length=10)
              'LASTNAME' => string 'VIGOUROUX' (length=9)
              'EMAIL' => string 'jvigouroux@openstudio.fr' (length=24)
              'RESELLER' => string '' (length=0)
              'SPONSOR' => string '' (length=0)
              'DISCOUNT' => string '' (length=0)
              'NEWSLETTER' => string '0' (length=1)
              'CREATE_DATE' =>
                array (size=3)
                  'date' => string '2016-04-22 14:32:42' (length=19)
                  'timezone_type' => int 3
                  'timezone' => string 'Europe/Paris' (length=12)
              'UPDATE_DATE' =>
                array (size=3)
                  'date' => string '2016-07-06 09:46:23' (length=19)
                  'timezone_type' => int 3
                  'timezone' => string 'Europe/Paris' (length=12)
              'LOOP_COUNT' => int 1
              'LOOP_TOTAL' => int 1
         */


        if ($status == '200' && is_array($data)) {
            if (isset($data[0]['ID']) && $data[0]['ID'] > 0) {
                $session = new Session();
                $session->set('isconnected', '1');
                $session->set('storecustomer', $data[0]);

                if (intval($form->get('remember_me')->getData()) > 0) {
                    // If a remember me field if present and set in the form, create
                    // the cookie thant store "remember me" information
                    $user = new TheliaStoreUser($data[0]['ID']);
                    $user->setUsername($data[0]['EMAIL']);
                    $user->setToken($data[0]['EMAIL']);
                    $user->setSerial(serialize($data[0]));

                    $this->createRememberMeCookie(
                        $user,
                        'theliastore',
                        2592000
                    );
                }
                //return $this->render('account-loginform');

                $this->setCurrentRouter('router.TheliaStore');
                return $this->generateRedirectFromRoute(
                    'theliastore.store',
                    [],
                    []

                );


            }
        }

        return $this->render('account-loginform');
    }

    public function loginFormAction()
    {
        return $this->render('account-loginform');
    }

    public function logoutAction()
    {
        $session = new Session();
        $session->remove('isconnected');
        $session->remove('storecustomer');

        $this->clearRememberMeCookie('theliastore');

        $this->setCurrentRouter('router.TheliaStore');
        return $this->generateRedirectFromRoute(
            'theliastore.store',
            [],
            []

        );
    }
}
