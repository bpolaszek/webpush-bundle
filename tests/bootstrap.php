<?php

namespace global {
    require dirname(__DIR__) . '/vendor/autoload.php';
}

// Those namespace overrides allow using https://github.com/bpolaszek/doctrine-static with a more actual Doctrine version
namespace Doctrine\Common\Persistence {

    use Doctrine\Persistence\ManagerRegistry as NewManagerRegistry;
    use Doctrine\Persistence\ObjectManager as NewObjectManager;
    use Doctrine\Persistence\ObjectRepository as NewObjectRepository;

    if (!interface_exists(ManagerRegistry::class)) {
        interface ManagerRegistry extends NewManagerRegistry
        {
        }
    }

    if (!interface_exists(ObjectManager::class)) {
        interface ObjectManager extends NewObjectManager
        {
        }
    }

    if (!interface_exists(ObjectRepository::class)) {
        interface ObjectRepository extends NewObjectRepository
        {
        }
    }

}
