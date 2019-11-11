<?php

declare(strict_types=1);

namespace Chiron\Container;

use ArrayAccess;
use Chiron\Container\Exception\ContainerException;
use Chiron\Container\ServiceProvider\ServiceProviderInterface;
use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

// TODO : créer une méthode singleton() ou share() => https://github.com/illuminate/container/blob/master/Container.php#L354
// https://github.com/thephpleague/container/blob/master/src/Container.php#L92
//https://github.com/mrferos/di/blob/master/src/Container.php#L99

// TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236

//TODO : Classe de TESTS pour les méthode register : https://github.com/laravel/framework/blob/master/tests/Foundation/FoundationApplicationTest.php

class Container extends ReflectionContainer implements FactoryInterface
{

    public function __construct()
    {
        parent::__construct();

        // TODO : ajouter un PHPunit pour vérifier si ces 4 classes sont bien ajoutées à la construction.
        // TODO : attention si on utilise ce bout de code, il faudra aussi faire une méthode __clone() qui remodifie ces valeurs d'instances. => https://github.com/Wandu/Framework/blob/master/src/Wandu/DI/Container.php#L65
        $this->share(Container::class, $this);
        $this->share(ContainerInterface::class, $this);
        $this->share(FactoryInterface::class, $this);
        $this->share(InvokerInterface::class, $this);
    }

    /*******************************************************************************
     * Build() new class or Call() callable
     ******************************************************************************/



    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param callable|string $callback
     * @param array           $parameters
     * @param string|null     $defaultMethod
     *
     * @return mixed
     */
    // TODO : améliorer le cas ou on passe uniquement une string sans le @__invoke qui est sous entendu !!! https://github.com/laravel/lumen-framework/blob/1a0855a5187af7bf3db1697cce05242dfb9271ea/src/Concerns/RoutesRequests.php#L305
    public function call($callback, array $parameters = [], ?string $defaultMethod = null)
    {
        /*
        if (is_string($callback) && strpos($callback, '@') === false) {
            $callback .= '@__invoke';
        }*/

        if ($this->isCallableWithAtSign($callback) || $defaultMethod) {
            return $this->callClass($callback, $parameters, $defaultMethod);
        }

        /*
        return $this->callBoundMethod($container, $callback, function () use ($container, $callback, $parameters) {
            return call_user_func_array(
                $callback, $this->getMethodDependencies($container, $callback, $parameters)
            );
        });*/

        if (! is_callable($callback)) {
            throw new InvalidArgumentException(sprintf(
                '(%s) is not resolvable.',
                is_array($callback) || is_object($callback) || is_null($callback) ? json_encode($callback) : $callback
            ));
        }

        return $this->resolver->call($callback, $parameters);
    }

    /**
     * Call a string reference to a class using Class@method syntax.
     *
     * @param string      $target
     * @param array       $parameters
     * @param string|null $defaultMethod
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    private function callClass(string $target, array $parameters = [], ?string $defaultMethod = null)
    {
        $segments = explode('@', $target);
        // We will assume an @ sign is used to delimit the class name from the method
        // name. We will split on this @ sign and then build a callable array that
        // we can pass right back into the "call" method for dependency binding.
        $method = count($segments) === 2 ? $segments[1] : $defaultMethod;

        if (is_null($method)) {
            throw new InvalidArgumentException('Method not provided.');
        }

        return $this->call([$this->get($segments[0]), $method], $parameters);
    }

    /**
     * Determine if the given string is in Class@method syntax.
     *
     * @param mixed $callback
     *
     * @return bool
     */
    private function isCallableWithAtSign($callback): bool
    {
        return is_string($callback) && strpos($callback, '@') !== false;
    }

}
