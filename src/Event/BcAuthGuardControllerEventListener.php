<?php
declare(strict_types=1);

namespace BcAuthGuard\Event;

use BcAuthCommon\Service\AuthLoginLogService;
use BaserCore\Event\BcControllerEventListener;
use BaserCore\Model\Table\LoginStoresTable;
use BaserCore\Service\DblogsService;
use BcAuthGuard\Service\BcAuthGuardService;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Cookie\Cookie;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\ORM\TableRegistry;

class BcAuthGuardControllerEventListener extends BcControllerEventListener
{
    public $events = [
        'BaserCore.Users.beforeLogin',
        'BaserCore.Users.afterLogin',
        'BaserCore.Users.beforeRender',
        'BaserCore.Users.beforeRedirect',
    ];

    private BcAuthGuardService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new BcAuthGuardService();
    }

    public function baserCoreUsersBeforeLogin(EventInterface $event): void
    {
        $controller = $event->getSubject();
        $request = $event->getData('user');
        if (!$request) {
            return;
        }
        if ((string) $request->getParam('prefix') !== 'Admin') {
            return;
        }
        if ((bool) $request->getAttribute('BcAuthGuard.beforeLoginHandled', false)) {
            return;
        }
        $request = $request->withAttribute('BcAuthGuard.beforeLoginHandled', true);
        $event->setData('user', $request);

        $prefix = 'Admin';
        $username = $this->service->normalizeUsername((string) $request->getData('email'));
        $ipAddress = (string) AuthLoginLogService::getRequestIp($request);
        $context = $this->buildContext($request);
        $sessionKey = (string) (Configure::read('BcPrefixAuth.Admin.sessionKey') ?? '');
        $hasAdminSession = $sessionKey !== '' && $request->getSession()->check($sessionKey);
        $this->debug('beforeLogin.enter', $controller, [
            'username' => $username,
            'ip' => $ipAddress,
            'method' => $request->getMethod(),
            'hasAdminSession' => $hasAdminSession,
        ]);

        if ($this->service->isBlockedIp($ipAddress)) {
            $this->debug('beforeLogin.blocked', $controller, [
                'username' => $username,
                'ip' => $ipAddress,
                'hasAdminSession' => $hasAdminSession,
            ]);

            if ($request->is('post')) {
                $this->service->recordBlockedIpDenied($prefix, $username, $ipAddress, $request, $context);
                $controller->BcMessage->setError(__d('baser_core', '申し訳ありませんが、ログインを制限しています。'), true);
                $event->setData('user', $request
                    ->withAttribute('BcAuthGuard.loginDenied', true)
                    ->withEnv('REQUEST_METHOD', 'GET'));
            }
            $this->forceLogoutIfAuthenticated($controller);
            return;
        }

        if (!$request->is('post')) {
            return;
        }

        if ($this->service->isLocked($prefix, $username, $ipAddress)) {
            $this->debug('beforeLogin.locked', $controller, [
                'username' => $username,
                'ip' => $ipAddress,
            ]);
            $this->service->recordLockoutDenied($prefix, $username, $ipAddress, $request, $context);
            $controller->BcMessage->setError(__d('baser_core', '入力を一定回数誤ったためログインを制限しています。しばらくしてから再試行してください。'), true);
            $this->forceLogoutIfAuthenticated($controller);
            $event->setData('user', $request
                ->withAttribute('BcAuthGuard.loginDenied', true)
                ->withEnv('REQUEST_METHOD', 'GET'));
        }
    }

    public function baserCoreUsersBeforeRender(EventInterface $event): void
    {
        $controller = $event->getSubject();
        $request = $controller->getRequest();
        if ((string) $request->getParam('prefix') !== 'Admin') {
            return;
        }
        if ((string) $request->getParam('action') !== 'login' || !$request->is('post')) {
            return;
        }
        if ((bool) $request->getAttribute('BcAuthGuard.beforeRenderHandled', false)) {
            return;
        }
        $request = $request->withAttribute('BcAuthGuard.beforeRenderHandled', true);
        $controller->setRequest($request);

        $result = $controller->Authentication->getResult();
        $this->debug('beforeRender.authResult', $controller, [
            'isValid' => $result?->isValid(),
            'method' => $request->getMethod(),
        ]);
        if ($result->isValid()) {
            return;
        }

        $prefix = 'Admin';
        $username = $this->service->normalizeUsername((string) $request->getData('email'));
        $ipAddress = (string) AuthLoginLogService::getRequestIp($request);
        $context = $this->buildContext($request);
        $lockedNow = $this->service->recordFailure($prefix, $username, $ipAddress, $request, $context);
        $this->debug('beforeRender.failureRecorded', $controller, [
            'username' => $username,
            'ip' => $ipAddress,
            'lockedNow' => $lockedNow,
        ]);
        if ($lockedNow) {
            $controller->BcMessage->setError(__d('baser_core', 'ログイン失敗が規定回数に達したため、一定時間ログインを制限しました。'), true);
        }
    }

    public function baserCoreUsersAfterLogin(EventInterface $event): void
    {
        $request = Router::getRequest();
        if (!$request || (string) $request->getParam('prefix') !== 'Admin') {
            return;
        }

        $subject = $event->getSubject();
        $user = $event->getData('user');
        if (!$user) {
            return;
        }
        $username = $this->service->normalizeUsername((string) ($user->email ?? ''));
        $eventClientIp = (string) ($event->getData('clientIp') ?? '');
        $ipAddress = filter_var($eventClientIp, FILTER_VALIDATE_IP)
            ? $eventClientIp
            : (string) AuthLoginLogService::getRequestIp($request);
        $this->debug('afterLogin.enter', $subject, [
            'username' => $username,
            'ip' => $ipAddress,
            'identityId' => $user->id ?? null,
        ]);

        $released = $this->service->clearFailures('Admin', $username, $ipAddress);
        $this->debug('afterLogin.clearFailures', $subject, [
            'username' => $username,
            'ip' => $ipAddress,
            'released' => $released,
        ]);
        if ($released) {
            $this->recordRecentActivity(__d('baser_core', '{0} のログイン制限を解除しました。', $username));
        }
    }

    public function baserCoreUsersBeforeRedirect(EventInterface $event)
    {
        $controller = $event->getSubject();
        $request = $controller->getRequest();
        if ((string) $request->getParam('prefix') !== 'Admin') {
            return;
        }
        if ((string) $request->getParam('action') !== 'login') {
            return;
        }
        if (!(bool) $request->getAttribute('BcAuthGuard.loginDenied', false)) {
            return;
        }

        $this->debug('beforeRedirect.denied', $controller, [
            'method' => $request->getMethod(),
            'url' => $request->getRequestTarget(),
        ]);

        // 拒否判定済みのリクエストでは、空レスポンスを避けるため明示的にログイン画面へ戻す
        $response = $controller->getResponse()->withLocation(Router::url([
            'prefix' => 'Admin',
            'plugin' => 'BaserCore',
            'controller' => 'Users',
            'action' => 'login',
        ], true));

        $event->stopPropagation();
        $event->setResult($response);
        return $response;
    }

    private function buildContext($request): array
    {
        return [
            'user_agent' => (string) $request->getHeaderLine('User-Agent'),
            'referer' => (string) $request->getHeaderLine('Referer'),
            'request_path' => (string) $request->getRequestTarget(),
            'payload' => (array) $request->getData(),
        ];
    }

    private function recordRecentActivity(string $message): void
    {
        try {
            (new DblogsService())->create(['message' => $message]);
        } catch (\Throwable $e) {
        }
    }

    private function forceLogoutIfAuthenticated($controller): void
    {
        try {
            $this->debug('forceLogout.start', $controller);
            if ($controller->components()->has('Authentication')) {
                $authentication = $controller->components()->get('Authentication');
                $identity = $authentication->getIdentity();
                if ($identity && !empty($identity->id)) {
                    TableRegistry::getTableLocator()->get('BaserCore.LoginStores')->removeKey('Admin', (int) $identity->id);
                    $this->debug('forceLogout.removeLoginKey', $controller, ['identityId' => (int) $identity->id]);
                }
                $authentication->logout();
                $this->clearAuthenticationResult($controller);
            }

            $session = $controller->getRequest()->getSession();
            $sessionKey = (string) (Configure::read('BcPrefixAuth.Admin.sessionKey') ?? '');
            if ($sessionKey !== '') {
                $session->delete($sessionKey);
            }
            $session->delete('BcAuthCommon.lastLogin.Admin');
            $session->delete('BcAuthCommon.authSource.Admin');
            $session->renew();

            $controller->setRequest($controller->getRequest()->withoutAttribute('identity'));

            $controller->setResponse(
                $controller->getResponse()->withExpiredCookie(new Cookie(LoginStoresTable::KEY_NAME))
            );
            $this->debug('forceLogout.done', $controller);
        } catch (\Throwable $e) {
            $this->debug('forceLogout.error', $controller, ['error' => $e->getMessage()]);
        }
    }

    private function clearAuthenticationResult($controller): void
    {
        try {
            if (!$controller->components()->has('Authentication')) {
                $this->debug('clearAuthenticationResult.skip', $controller);
                return;
            }
            $authentication = $controller->components()->get('Authentication');
            $service = $authentication->getAuthenticationService();
            $result = $service->getResult();
            if ($result) {
                $resultReflection = new \ReflectionObject($result);
                if ($resultReflection->hasProperty('_status')) {
                    $statusProperty = $resultReflection->getProperty('_status');
                    $statusProperty->setValue($result, 'FAILURE_CREDENTIALS_INVALID');
                }
                if ($resultReflection->hasProperty('_data')) {
                    $dataProperty = $resultReflection->getProperty('_data');
                    $dataProperty->setValue($result, null);
                }
            }

            $reflection = new \ReflectionObject($service);

            foreach (['_result', '_successfulAuthenticator'] as $propertyName) {
                if (!$reflection->hasProperty($propertyName)) {
                    continue;
                }
                $property = $reflection->getProperty($propertyName);
                if ($propertyName === '_result' && $result) {
                    $property->setValue($service, $result);
                } else {
                    $property->setValue($service, null);
                }
            }
            $this->debug('clearAuthenticationResult.done', $controller);
        } catch (\Throwable $e) {
            $this->debug('clearAuthenticationResult.error', $controller, ['error' => $e->getMessage()]);
        }
    }

    private function debug(string $label, $controller, array $context = []): void
    {
        try {
            $request = $controller->getRequest();
            $session = $request->getSession();
            $authResult = null;
            $identityId = null;
            if (isset($controller->Authentication)) {
                $authResult = $controller->Authentication->getResult()?->isValid();
                $identityId = $controller->Authentication->getIdentity()?->id ?? null;
            }

            Log::debug('[BcAuthGuard] ' . $label . ' ' . json_encode(array_merge([
                'prefix' => (string) $request->getParam('prefix'),
                'action' => (string) $request->getParam('action'),
                'method' => $request->getMethod(),
                'url' => (string) $request->getRequestTarget(),
                'loginDenied' => (bool) $request->getAttribute('BcAuthGuard.loginDenied', false),
                'authResultValid' => $authResult,
                'identityId' => $identityId,
                'sessionHasAdminAuth' => $session->check((string) (Configure::read('BcPrefixAuth.Admin.sessionKey') ?? '')),
                'hasLoginStoreCookie' => $request->getCookie(LoginStoresTable::KEY_NAME) !== null,
                'userAgent' => (string) $request->getHeaderLine('User-Agent'),
                'remoteIp' => (string) AuthLoginLogService::getRequestIp($request),
            ], $context), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
        }
    }

}
