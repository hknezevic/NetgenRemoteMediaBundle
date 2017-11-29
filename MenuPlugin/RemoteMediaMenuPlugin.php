<?php

namespace Netgen\Bundle\RemoteMediaBundle\MenuPlugin;

use Netgen\Bundle\AdminUIBundle\MenuPlugin\MenuPluginInterface;
use Symfony\Component\HttpFoundation\Request;

class RemoteMediaMenuPlugin implements MenuPluginInterface
{
    /**
     * Returns plugin identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return 'ngremotemedia';
    }

    /**
     * Returns the list of templates this plugin supports.
     *
     * @return array
     */
    public function getTemplates()
    {
        return array(
            'aside' => 'NetgenRemoteMediaBundle:ngadminui/plugin/dashboard:aside.html.twig',
            'left' => 'NetgenRemoteMediaBundle:ngadminui/plugin/dashboard:left.html.twig'
        );
    }

    /**
     * Returns if the menu is active.
     *
     * @return bool
     */
    public function isActive()
    {
        // @todo: should be active only if provider is Cloudinary
        return true;
    }

    /**
     * Returns if this plugin matches the current request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return bool
     */
    public function matches(Request $request)
    {
        $route = $request->attributes->get('_route');

        return mb_substr($route, 0, strlen('ngrm.ngadmin.cloudinary')) === 'ngrm.ngadmin.cloudinary';
    }
}
