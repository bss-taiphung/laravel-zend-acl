<?php namespace Spekkionu\ZendAcl;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Config\Repository as Config;
use Zend\Permissions\Acl\Acl;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class AclRouteFilter
{

    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * The Acl implementation.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * The Config implementation.
     *
     * @var Config
     */
    protected $config;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth, Acl $acl, Config $config)
    {
        $this->auth = $auth;
        $this->acl = $acl;
        $this->config = $config;
    }

    /**
     * Route filter method
     *
     * @param Route $route
     * @param Request $request
     * @param string $resource
     * @param string $permission
     *
     * @return \Illuminate\Http\Response
     */
    public function filter(Route $route, Request $request, $resource = null, $permission = null)
    {
        if ($this->auth->guest()) {
            if (!$this->acl->isAllowed('guest', $resource, $permission)) {
                return $this->notAllowed($request);
            }
        } elseif (!$this->acl->isAllowed($this->auth->user(), $resource, $permission)) {
            return $this->notAllowed($request);
        }
    }

    /**
     * Processes not allowed response
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    protected function notAllowed(Request $request)
    {
        if ($request->ajax()) {
            return response('Unauthorized.', 401);
        } else {
            $action = $this->config->get('zendacl.action', 'redirect');
            if ($action == 'redirect') {
                $url = $this->config->get('zendacl.redirect', 'auth/login');
                return redirect($url);
            } elseif ($action == 'route') {
                $route = $this->config->get('zendacl.redirect');
                return redirect()->route($route);
            } elseif ($action == 'view') {
                $view = $this->config->get('zendacl.view', 'zendacl::unauthorized');
                return view($view);
            }
        }

    }
}
