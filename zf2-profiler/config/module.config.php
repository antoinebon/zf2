<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Profiler\ProfilerController' => 'Profiler\Controller\ProfilerController',
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'db' => 'Profiler\Db\ProfilerAdapterAbstractServiceFactory',
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    'router' => array(
        'routes' => array(
            'profiler' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/profiler/:action[/page/:page]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'page' => '[0-9]*',
                    ),
                    'defaults' => array(
                        'controller' => 'Profiler\ProfilerController',
                        /* 'action' => 'index', */
                        'page' => 1,
                        'permissions' => array(
                            'resource' => 'profiler',
                            'privilege' => 'view',
                        ),
                    ),
                ),
            ),
            'profiler-static' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/profiler/public/:resource{?}',
                    'constraints' => array(
                        'filePath' => '[a-zA-Z][a-zA-Z0-9_-\.]*',
                    ),
                    'defaults' => array(
                        'controller' => 'Profiler\ProfilerController',
                        'action' => 'static',
                        'permissions' => array(
                            'resource' => 'profiler',
                            'privilege' => 'view',
                        ),
                    ),
                ),
            ),
        ),
    ),
    'profiler' => array(
        'profiler' => array(
            'enabled' => false,
            'paginator' => array(
                'itemCountPerPage' => 40,
            ),
            'mongodb' => array(
                'server' => 'mongodb://localhost:27017',
                'database' => 'log',
                'collection' => 'profiler',
            ),
            'portalHomeRouteParams' => array(
                'dashboard', // the route name
                array('action' => 'index'), // the route params
                array(), // the route options
            ),
        ),
    ),
);
