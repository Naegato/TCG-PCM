<?php

namespace App;

use App\Badge\Handler\BadgeHandlerInterface;
use App\DependencyInjection\DeployPass;
use App\DependencyInjection\RegisterGameHelperPass;
use App\DependencyInjection\UseRedisGameStateRepositoryPass;
use App\Game\GameUtils;
use App\Interface\DeployAwareInterface;
use App\Service\EndGameHandler;
use App\Tests\Resources\MockCardRegistry;
use App\Tests\Resources\MockHub;
use App\Utils\KillSwitch;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Mercure\HubInterface;

class Kernel extends BaseKernel
{
    use MicroKernelTrait {
        configureContainer as private defaultConfigureContainer;
    }

    public function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $this->defaultConfigureContainer($container, $loader, $builder);
        $gameConfigDir = $this->getConfigDir().'/game';

        $loader->load($gameConfigDir.'/game.php');

        if ($builder->hasParameter('kernel.debug') && $builder->getParameter('kernel.debug')) {
            $loader->load($gameConfigDir.'/debug.php');
        }

        if ('test' === $container->env()) {
            $builder->register('game.card_registry', MockCardRegistry::class);
            $builder->register(HubInterface::class, MockHub::class);
        }

        $builder->setAlias('game.end_game_handler', EndGameHandler::class);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(BadgeHandlerInterface::class)->addTag('app.badge_handler');
        $container->registerForAutoconfiguration(DeployAwareInterface::class)->addTag('app.deploy', ['method' => 'onDeploy']);

        $container->addCompilerPass(new UseRedisGameStateRepositoryPass());
        $container->addCompilerPass(new DeployPass());
        $container->addCompilerPass(new RegisterGameHelperPass());

        $container
            ->register('kernel.get_feature', \Closure::class)
            ->setFactory([\Closure::class, 'fromCallable'])
            ->setArguments([
                [new Reference(KillSwitch::class), 'isEnable'],
            ])
            ->addTag('routing.expression_language_function', [
                'function' => 'is_enable',
            ]);

        $container
            ->register('kernel.get_env', \Closure::class)
            ->setFactory([\Closure::class, 'fromCallable'])
            ->setArguments([
                [new Reference('kernel'), 'getEnvironment'],
            ])
            ->addTag('routing.expression_language_function', [
                'function' => 'get_env',
            ]);
    }

    public function boot(): void
    {
        parent::boot();

        GameUtils::setContainer($this->container->get('game.container'));
    }
}
