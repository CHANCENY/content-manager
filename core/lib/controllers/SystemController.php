<?php

namespace Simp\Core\lib\controllers;

use Google\Service\Exception;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\forms\AccountSettingForm;
use Simp\Core\lib\forms\BasicSettingForm;
use Simp\Core\lib\forms\LoginForm;
use Simp\Core\lib\forms\ProfileEditForm;
use Simp\Core\lib\forms\UserAccountEditForm;
use Simp\Core\lib\memory\session\Session;
use Simp\Core\lib\themes\View;
use Simp\Core\modules\auth\AuthenticationSystem;
use Simp\Core\modules\auth\normal_auth\AuthUser;
use Simp\Core\modules\config\ConfigManager;
use Simp\Core\modules\messager\Messager;
use Simp\Core\modules\user\current_user\CurrentUser;
use Simp\Core\modules\user\entity\User;
use Simp\FormBuilder\FormBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class SystemController
{
    /**
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function toastify_controller(...$args): JsonResponse
    {
        $messages = Session::init()->get('system.messages');
        Session::init()->delete('system.messages');
        return new JsonResponse($messages);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function people_controller(...$args): Response
    {
        extract($args);
        /**@var Request $request**/
        $limit = $request->get('limit', 10);
        $limit = empty($limit) ? 10 : $limit;
        $filters = User::filters('users', $limit);
        $users = User::parseFilter(User::class, 'users', $filters, $request, User::class);
        return new Response(View::view('default.view.people',['users' => $users, 'filters' => $filters]));
    }

    /**
     * @param ...$args
     * @return RedirectResponse|Response
     * @throws LoaderError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function account_delete_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        /**@var Request $request**/

        // Let's look for account delete confirmation.
        $is_confirmed = $request->get('confirm', null);
        if ($is_confirmed === "yes") {
            Session::init()->set('system.deletion.confirmation', 'yes');
            $config = ConfigManager::config()->getConfigFile('account.setting');
            $user = User::load($request->get('uid'));

            if ($config?->get('cancellation') === 'blocked') {

                $user->setStatus(0);
                if ($user->update()) {
                    return new RedirectResponse('/admin/people');
                }

            }
            elseif ($config?->get('cancellation') === 'unpublished') {
                $user->setStatus(0);
                //TODO: update the nodes.
                if ($user->update()) {
                    return new RedirectResponse('/admin/people');
                }
            }

            if (User::dataDeletion('users', 'uid', $request->get('uid'))) {
                return new RedirectResponse('/admin/people');
            }
        }
        elseif ($is_confirmed === "no") {
            return new RedirectResponse($request->server->get('REDIRECT_URL'));
        }

        return new Response(View::view('default.view.delete_confirmation',[
            'title' => "Account Deletion Confirmation",
            'message' => "You are requesting to delete your account. Are you sure you want to proceed?",
        ]));
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function account_edit_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $form_base = new FormBuilder(new UserAccountEditForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.edit.account.form',['_form'=>$form_base]),200);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function account_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $user = User::load($request->get('uid'));
        return new Response(View::view('default.view.view.account', ['user'=>$user]),200);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function user_login_form_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $form_base = new FormBuilder(new LoginForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        $field = $form_base->getFields();
        $authenticate = new AuthenticationSystem();
        $google_link = null;
        $github_link = null;
        if ($authenticate->isGoogleAuthActive()) {
            $google = $authenticate->getOauthInstance('google');
            $google_link = $google->generateLoginUrl();
        }

        if ($authenticate->isGithubAuthActive()) {
            $github = $authenticate->getOauthInstance('github');
            $github_link = $github->generateLoginUrl();
        }

        return new Response(View::view('default.view.view.login.form',[
            'name'=> $field['name'],
            'password'=> $field['password'],
            'auth' => $authenticate,
            'google_link' => $google_link,
            'github_link' => $github_link,
        ]),200);
    }

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function user_logout_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $auth = CurrentUser::currentUser();
        if ($auth->logout()) {
            return new RedirectResponse('/');
        }
        return new RedirectResponse($request->server->get('REDIRECT_URL'));
    }


    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function account_profile_edit_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $form_base = new FormBuilder(new ProfileEditForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.profile.form.edit', ['_form'=>$form_base]),200);
    }

    /**
     * @param ...$args
     * @return RedirectResponse|Response
     * @throws Exception
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function user_login_google_redirect_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        /**@var Request $request**/
        $google_code = $request->get('code');
        $authenticate = new AuthenticationSystem();
        $google_instance = $authenticate->getOauthInstance('google');
        if ($google_code) {

            $token = $google_instance->fetchAccessTokenWithAuthCode($google_code);
            $google_instance->setAccessToken($token);
            $user = $google_instance->oauth2Profile();
            $auth = AuthUser::auth();
            if ($auth->authenticateViaGoogle($user)) {
                $auth->finalizeAuthenticate(false);
                Messager::toast()->addMessage("Welcome back, {$auth->getUser()->getName()}!");
                return new RedirectResponse('/');
            }
            Messager::toast()->addError("Sorry login via google account has failed.");
            return new RedirectResponse('/user/login');
        }
        return new Response('');
    }

    /**
     * @param ...$args
     * @return RedirectResponse|Response
     * @throws GuzzleException
     * @throws IdentityProviderException
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function user_login_github_redirect_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $github_code = $request->get('code');
        $authenticate = new AuthenticationSystem();
        $github_instance = $authenticate->getOauthInstance('github');
        if ($github_code) {
            $token = $github_instance->getAccessToken($github_code);
            $git_user = $github_instance->getResourceOwner($token);
            $auth = AuthUser::auth();
            if ($auth->authenticateViaGithub($git_user)) {
                $auth->finalizeAuthenticate(false);
                Messager::toast()->addMessage("Welcome back, {$auth->getUser()->getName()}!");
                return new RedirectResponse('/');
            }else {
                Messager::toast()->addError("Sorry login via github account has failed.");
                return new RedirectResponse('/user/login');
            }
        }
        return new RedirectResponse('/');
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function configuration_controller(...$args): RedirectResponse|Response
    {
        return new Response(View::view('default.view.configuration', ['_form']), 200);
    }

    public function configuration_basic_site_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $form_base = new FormBuilder(new BasicSettingForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.configuration.basic.site', ['_form'=>$form_base]), 200);
    }

    public function configuration_account_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $form_base = new FormBuilder(new AccountSettingForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.configuration.accounts', ['_form'=>$form_base]), 200);
    }

    public function configuration_smtp_controller(...$args): RedirectResponse|Response
    {
        extract($args);

        return new Response(View::view('default.view.configuration.smtp', ['_form'=>$form_base]), 200);
    }
}