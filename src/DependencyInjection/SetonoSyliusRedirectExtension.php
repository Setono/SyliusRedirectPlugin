<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class SetonoSyliusRedirectExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{driver: string, resources: array<string, mixed>, remove_after: int, automatic_redirects: array<string, bool>} $config */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $container->setParameter('setono_sylius_redirect.remove_after', $config['remove_after']);

        $automaticRedirects = array_filter($config['automatic_redirects']);
        $container->setParameter('setono_sylius_redirect.automatic_redirects', $automaticRedirects);

        $loader->load('services.php');

        $this->registerResources('setono_sylius_redirect', SyliusResourceBundle::DRIVER_DOCTRINE_ORM, $config['resources'], $container);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('sylius_grid', [
            'grids' => [
                'setono_sylius_redirect_admin_redirect' => [
                    'driver' => [
                        'name' => 'doctrine/orm',
                        'options' => [
                            'class' => '%setono_sylius_redirect.model.redirect.class%',
                        ],
                    ],
                    'fields' => [
                        'source' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_redirect.ui.source',
                        ],
                        'destination' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_redirect.ui.destination',
                        ],
                        'permanent' => [
                            'type' => 'twig',
                            'label' => 'setono_sylius_redirect.ui.permanent',
                            'options' => [
                                'template' => '@SyliusUi/grid/field/yes_no.html.twig',
                            ],
                        ],
                        'enabled' => [
                            'type' => 'twig',
                            'label' => 'setono_sylius_redirect.ui.enabled',
                            'options' => [
                                'template' => '@SyliusUi/grid/field/yes_no.html.twig',
                            ],
                        ],
                        'count' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_redirect.ui.count',
                        ],
                        'lastAccessed' => [
                            'type' => 'datetime',
                            'label' => 'setono_sylius_redirect.ui.last_accessed',
                        ],
                    ],
                    'filters' => [
                        'search' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.search',
                            'form_options' => [
                                'type' => 'contains',
                            ],
                            'options' => [
                                'fields' => ['source', 'destination'],
                            ],
                        ],
                        'enabled' => [
                            'type' => 'boolean',
                            'label' => 'setono_sylius_redirect.ui.enabled',
                        ],
                        'permanent' => [
                            'type' => 'boolean',
                            'label' => 'setono_sylius_redirect.ui.permanent',
                        ],
                        'only404' => [
                            'type' => 'boolean',
                            'label' => 'setono_sylius_redirect.form.redirect.only_404',
                        ],
                        'channel' => [
                            'type' => 'entity',
                            'label' => 'setono_sylius_redirect.ui.channels',
                            'form_options' => [
                                'class' => '%sylius.model.channel.class%',
                            ],
                            'options' => [
                                'fields' => ['channels.id'],
                            ],
                        ],
                    ],
                    'actions' => [
                        'main' => [
                            'create' => ['type' => 'create'],
                        ],
                        'item' => [
                            'show' => ['type' => 'show'],
                            'update' => ['type' => 'update'],
                            'delete' => ['type' => 'delete'],
                        ],
                        'bulk' => [
                            'delete' => ['type' => 'delete'],
                        ],
                    ],
                ],
            ],
        ]);

        $formSections = [
            'general' => ['enabled' => false],
            'source_destination' => [
                'template' => '@SetonoSyliusRedirectPlugin/admin/redirect/form/sections/source_destination.html.twig',
                'priority' => 200,
            ],
            'options' => [
                'template' => '@SetonoSyliusRedirectPlugin/admin/redirect/form/sections/options.html.twig',
                'priority' => 100,
            ],
            'channels' => [
                'template' => '@SetonoSyliusRedirectPlugin/admin/redirect/form/sections/channels.html.twig',
                'priority' => 50,
            ],
        ];

        $container->prependExtensionConfig('sylius_twig_hooks', [
            'hooks' => [
                'sylius_admin.redirect.create.content.form.sections' => $formSections,
                'sylius_admin.redirect.update.content.form.sections' => $formSections,
                'sylius_admin.redirect.show.content' => [
                    'sections' => [
                        'template' => '@SetonoSyliusRedirectPlugin/admin/redirect/show/content/sections.html.twig',
                        'priority' => 0,
                    ],
                ],
                'sylius_admin.redirect.show.content.sections' => [
                    'general' => [
                        'template' => '@SetonoSyliusRedirectPlugin/admin/redirect/show/content/sections/general.html.twig',
                        'priority' => 0,
                    ],
                ],
            ],
        ]);
    }
}
