<?php

namespace Simp\Core\lib\controllers;

use Simp\Core\lib\forms\ContentTypeInnerFieldEditForm;
use Simp\Core\modules\assets_manager\AssetsManager;
use Simp\Core\modules\logger\ErrorLogger;
use Simp\Core\modules\logger\ServerLogger;
use Simp\Core\modules\structures\content_types\form\ContentTypeDefinitionEditForm;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Twig\Error\RuntimeError;
use Google\Service\Exception;
use Simp\Core\lib\themes\View;
use Simp\FormBuilder\FormBuilder;
use Simp\Core\lib\forms\LoginForm;
use Simp\Core\lib\forms\SiteSmtpForm;
use Simp\Core\modules\user\entity\User;
use Simp\Core\lib\forms\ContentTypeForm;
use Simp\Core\lib\forms\DevelopmentForm;
use Simp\Core\lib\forms\ProfileEditForm;
use Simp\Core\modules\messager\Messager;
use GuzzleHttp\Exception\GuzzleException;
use Simp\Core\lib\forms\BasicSettingForm;
use Simp\Core\lib\memory\session\Session;
use Simp\Core\lib\forms\AccountSettingForm;
use Simp\Core\modules\config\ConfigManager;
use Simp\Core\lib\forms\ContentTypeEditForm;
use Simp\Core\lib\forms\UserAccountEditForm;
use Simp\Core\lib\forms\ContentTypeFieldForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Simp\Core\modules\auth\AuthenticationSystem;
use Simp\Core\modules\auth\normal_auth\AuthUser;
use Simp\Core\lib\forms\ContentTypeFieldEditForm;
use Simp\Core\lib\forms\ContentTypeInnerFieldForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Simp\Core\modules\user\current_user\CurrentUser;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Simp\Core\modules\structures\content_types\entity\Node;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Simp\Core\modules\structures\content_types\ContentDefinitionManager;
use Simp\Core\modules\structures\content_types\form\ContentTypeDefinitionForm;

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

    public function system_error_page_denied(...$args): Response
    {
        return new Response("Access denied. sorry you can acess this page", 403);
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
            return new RedirectResponse('/user/'. $request->get('uid'));
        }

        return new Response(View::view('default.view.delete_confirmation',[
            'title' => "Account Deletion Confirmation",
            'message' => "You are requesting to delete your account. Are you sure you want to proceed?",
        ]));
    }

    public function assets_loader_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $name = $request->get('name');
        $file_path = (new AssetsManager())->getAssetsFile($name,false);
        if (!empty($file_path) && file_exists($file_path)) {
            $mime_type = mime_content_type($file_path);
            $response = new Response(
                file_get_contents($file_path),
            );
            $response->headers->set('Content-Type', $mime_type);
            $response->setStatusCode(200);
            return $response;
        }
        return new Response('', 404);
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

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
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
        $form_base = new FormBuilder(new SiteSmtpForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.configuration.smtp', ['_form'=>$form_base]), 200);
    }

    public function configuration_logger_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $form_base = new FormBuilder(new DevelopmentForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.configuration.logger', ['_form'=>$form_base]), 200);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function structure_controller(...$args): RedirectResponse|Response
    {
        return new Response(View::view('default.view.structure'), 200);
    }

    public function structure_content_type_controller(...$args): RedirectResponse|Response
    {
        $manager = ContentDefinitionManager::contentDefinitionManager()->getContentTypes();
        return new Response(View::view('default.view.content-types-listing',['items'=>$manager]), 200);
    }

    public function structure_content_type_form_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $form_base = new FormBuilder(new ContentTypeForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.structure_content_type',['_form'=> $form_base]), 200);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function content_type_edit_form_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $name = $request->get('machine_name');
        $content = ContentDefinitionManager::contentDefinitionManager()->getContentType($name);
        $form_base = new FormBuilder(new ContentTypeEditForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.structure_content_type_edit',['_form'=> $form_base, 'content'=>$content]), 200);
    }

    /**
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function content_type_delete_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $name = $request->get('machine_name');
        $content = ContentDefinitionManager::contentDefinitionManager()->getContentType($name);
        if ($content && ContentDefinitionManager::contentDefinitionManager()->removeContentType($name)) {
            Messager::toast()->addMessage("Content type \"$name\" successfully removed.");
        }
        return new RedirectResponse('/admin/structure/types');
    }

    public function content_type_manage_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $name = $request->get('machine_name');
        $content = ContentDefinitionManager::contentDefinitionManager()->getContentType($name);
        if ($request->getMethod() === 'POST') {
            $data = $request->request->all();
            if(isset($data['display_submit'])) {
                $settings = [];
                $storages = $content['storage'] ?? [];
                foreach($storages as $storage) {
                    $name_field = substr($storage, 5, strlen($storage));
                    $name_field = trim($name_field, '_');
                    $settings[$name_field]['display_label'] = $data[$name_field. ':display_label'] ?? null;
                    $settings[$name_field]['display_as'] = $data[$name_field . ':display_as'] ?? null;
                    $settings[$name_field]['display_enabled'] = $data[$name_field . ':display_enabled'] ?? null;
                }
                $content['display_setting'] = $settings;
                ContentDefinitionManager::contentDefinitionManager()->addContentType($name, $content);
                Messager::toast()->addMessage("Display setting saved");
                return new RedirectResponse('/admin/structure/content-type/'.$name.'/manage');
            }
        }
        return new Response(View::view('default.view.structure_content_type_manage',['content'=>$content]), 200);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function content_type_manage_add_field_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $form_base = new FormBuilder(new ContentTypeFieldForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.structure_content_type_manage_add_field',['_form'=>$form_base]), 200);
    }

    public function content_type_manage_edit_field_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        extract($args);
        $form_base = new FormBuilder(new ContentTypeFieldEditForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.structure_content_type_manage_add_field',['_form'=>$form_base]), 200);
    }

    /**
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function content_type_manage_delete_field_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $name = $request->get('machine_name');
        $field_name = $request->get('field_name');
        if (ContentDefinitionManager::contentDefinitionManager()->removeField($name,$field_name)) {
            Messager::toast()->addMessage("Content type field \"$field_name\" successfully removed.");
        }
        return new RedirectResponse('/admin/structure/content-type/'.$name.'/manage');
    }

    public function content_node_add_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $form_base = new FormBuilder(new ContentTypeDefinitionForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        $content = $request->get('content_name');
        if (empty($content)) {
            Messager::toast()->addWarning("Content type not found.");
            return new RedirectResponse('/');
        }
        $content = ContentDefinitionManager::contentDefinitionManager()->getContentType($content);
        return new Response(View::view('default.view.content_node_add',['_form'=>$form_base, 'content' => $content]), 200);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function content_content_admin_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        /**@var Request $request**/
        /**@var Request $request**/
        $limit = $request->get('limit', 10);
        $limit = empty($limit) ? 10 : $limit;
        $filters = Node::filters('node_data', $limit);
        $nodes = Node::parseFilter(Node::class, 'node_data', $filters, $request, Node::class);
        return new Response(View::view('default.view.content_content_admin',['nodes' => $nodes, 'filters' => $filters]), 200);
    }

    public function content_content_node_add_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $content_list = ContentDefinitionManager::contentDefinitionManager()->getContentTypes();
        return new Response(View::view('default.view.content_content_admin_node_add',['contents' => $content_list]), 200);
    }

    public function content_structure_field_inner_manage_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $fields = ContentDefinitionManager::contentDefinitionManager()
                  ->getContentType($request->get('machine_name'));
        $inner_fields = $fields['fields'][$request->get('field_name')]['inner_field'] ?? [];

        return new Response(View::view('default.view.content_structure_field_inner_manage',
         ['fields'=>$inner_fields, 'content'=> $fields, 'parent_field'=> $request->get('field_name')]));
    }

    public function content_structure_field_inner_add_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $form_base = new FormBuilder(new ContentTypeInnerFieldForm);
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.structure_content_type_manage_add_field', ['_form' => $form_base, 'parent_field'=>$request->get('field_name')]), 200);
    }

    public function content_structure_field_inner_edit_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $form_base = new FormBuilder(new ContentTypeInnerFieldEditForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.structure_content_type_manage_add_field', ['_form' => $form_base, 'parent_field'=>$request->get('field_name')]), 200);
    }

    public function content_type_manage_delete_inner_field_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $name = $request->get('machine_name');
        $field_name = $request->get('field_name');
        $parent_field = $request->get('parent_name');
        if (ContentDefinitionManager::contentDefinitionManager()->removeInnerField($name,$parent_field,$field_name)) {
            Messager::toast()->addMessage("Content type field \"$field_name\" successfully removed.");
        }
        return new RedirectResponse('/admin/structure/content-type/'.$name.'/manage');
    }

    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function content_node_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $nid = $request->get('nid');
        if (empty($nid)) {
            Messager::toast()->addWarning("Node id not found.");
            return new RedirectResponse('/');
        }
        try{
            $node = Node::load($nid);
            $entity = $node->getEntityArray();
            $definitions = [];
            foreach ($entity['storage'] as $field) {
                $name = substr($field,6,strlen($field));
                $definitions[$name] = Node::findField($entity['fields'], $name);
            }
            return new Response(View::view('default.view.content_node_controller',[
                'node'=>$node,
                'definitions'=>$definitions
            ]));
        }catch (Throwable $exception){
            return new RedirectResponse('/');
        }
    }

    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function content_node_add_edit_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $nid = $request->get('nid');
        if (empty($nid)) {
            Messager::toast()->addWarning("Node id not found.");
            return new RedirectResponse('/');
        }
        $form_base = new FormBuilder(new ContentTypeDefinitionEditForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        $obj = Node::load($nid);
        if (is_null($obj)) {
            Messager::toast()->addWarning("Node not found");
            return new Response('/');
        }
        $content = ContentDefinitionManager::contentDefinitionManager()->getContentType($obj->getBundle());
        return new Response(View::view('default.view.content_node_add',['_form'=>$form_base, 'content' => $content]), 200);
    }

    /**
     * @throws RuntimeError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws LoaderError
     * @throws SyntaxError
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     */
    public function content_node_add_delete_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $nid = $request->get('nid');
        if (empty($nid)) {
            Messager::toast()->addWarning("Node id not found.");
            return new RedirectResponse('/');
        }
        $node = Node::load($nid);
        if (empty($request->get('action'))) {
            return new Response(View::view('default.view.confirm.content_node_delete',['node'=>$node]));
        }
        $title = $node->getTitle();
        if ((int) $request->get('action') == 3) {
            return new RedirectResponse('/node/'.$node->getNid());
        }
        if ($node->delete((int) $request->get('action'))) {
            Messager::toast()->addMessage("Node \"$title\" successfully deleted.");
            return $request->get('action') == 1 ? new RedirectResponse('/') : new RedirectResponse('/node/'.$node->getNid());
        }
        Messager::toast()->addWarning("Node \"$title\" was not deleted.");
        return new RedirectResponse('/node/'.$node->getNid());
    }


    public function admin_report_site_controller(...$args): RedirectResponse|Response
    {
        extract($args);
        $offset =  $request->get('offset', 1);
        $limit =  $request->get('limit', 124);
        $server = new ServerLogger(limit: $limit, offset: $offset);
        $errors = new ErrorLogger(read: true);
        return new Response(View::view('default.view.admin_report_site_controller',
            [
                'server'=>$server->getLogs(),
                'server_filter' => $server->getFilterNumber(2),
                'offset' => $offset,
                'limit' => $limit,
                'errors'=>$errors->getLogs(),
            ]
        ), 200);
    }
}