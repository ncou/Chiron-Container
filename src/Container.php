<?php

declare(strict_types=1);

namespace Chiron\Container;

use ArrayAccess;
use Chiron\Container\Exception\ContainerException;
use Closure;
use InvalidArgumentException;

// TODO : créer une méthode singleton() ou share() => https://github.com/illuminate/container/blob/master/Container.php#L354
// https://github.com/thephpleague/container/blob/master/src/Container.php#L92
//https://github.com/mrferos/di/blob/master/src/Container.php#L99

// TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236

class Container extends ContainerAbstract implements ArrayAccess
{
    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
     *
     * @param \Closure $callback
     * @param array    $parameters
     *
     * @return \Closure
     */
    // https://github.com/illuminate/container/blob/master/Container.php#L556
    // TODO : le paramétre $callback ne devrait pas plutot être du type callable au lieu de Closure ????? / voir même de type string car le call support les string avec le signe @
    public function wrap(Closure $closure, array $parameters = []): Closure
    {
        return function () use ($closure, $parameters) {
            return $this->call($closure, $parameters);
        };
    }

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @param string $abstract
     *
     * @return \Closure
     */
    //https://github.com/illuminate/container/blob/master/Container.php#L582
    public function factory(string $abstract): Closure
    {
        return function () use ($abstract) {
            // this will resolve the item (so instanciate class or execute closure)
            return $this->get($abstract, true);
        };
    }

    /*
    // TODO : regarder si on peut utiliser cette méthode pour tester le has() !!!!
        public function has($name): bool
        {
            //TODO : on devrait faire une vérif si le paramétre $alias est bien une string sinon on léve une exception !!!!!
            return array_key_exists($name, $this->services) || $this->isAlias($name);
        }
    */

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param string $abstract
     *
     * @return bool
     */
    /*
    //https://github.com/illuminate/container/blob/master/Container.php#L158
    public function bound($abstract)
    {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               $this->isAlias($abstract);
    }*/
    /**
     *  {@inheritdoc}
     */
    /*
    public function has($id)
    {
        return $this->bound($id);
    }*/

    /**
     * {@inheritdoc}
     */
    // TODO : à virer c'est déprecated !!!!
    /*
    public function instance(string $name, $value): DefinitionInterface
    {
        $this->destroy($name);
        $this->instances[$name] = $value;

        return $this->definitions[$name] = new Definition($name, $value);
        //return $this->definitions[] = new Definition($name);
    }*/

    // TODO : renommer en invokable()
    /*
    public function closure(string $name, callable $handler): DefinitionInterface
    {
        return $this->bind($name, $handler);
    }*/

    /**
     * {@inheritdoc}
     */
    // TODO : lui passer un 3 eme paramétre pour savoir si c'est du shared ou non + créer une méthode share() => https://github.com/thephpleague/container/blob/master/src/Container.php#L92
    /*
    public function bind2(string $name, $className = null): DefinitionInterface
    {
        if (! isset($className)) {
            $this->destroy($name);
            $this->classes[$name] = $name;

            return $this->definitions[$name] = new Definition($name, $name);
            //return $this->definitions[] = new Definition($name);
        }
        if (is_string($className) && class_exists($className)) {
            $this->destroy($name, $className);
            $this->classes[$className] = $className;
            $this->alias($name, $className);

            return $this->definitions[$className] = new Definition($className, $className);
            //return $this->definitions[] = new Definition($className);
        } elseif (is_callable($className)) {
            $this->destroy($name);
            $this->closures[$name] = $className;

            return $this->definitions[$name] = new Definition($name, $className);
            //return $this->definitions[] = new Definition($name);
        }

        throw new ContainerException(
            sprintf('Argument 2 must be class name or Closure, "%s" given', is_object($className) ? get_class($className) : gettype($className))
        );
    }*/

    /**
     * {@inheritdoc}
     */
    // TODO : améliorer le code regarder ici   =>   https://github.com/illuminate/container/blob/master/Container.php#L778
    // TODO : améliorer le code et regarder ici => https://github.com/thephpleague/container/blob/68c148e932ef9959af371590940b4217549b5b65/src/Definition/Definition.php#L225
    // TODO : attention on ne gére pas les alias, alors que cela pourrait servir si on veut builder une classe en utilisant l'alias qui est présent dans le container. Réfléchir si ce cas peut arriver.
    // TODO : renommer en buildClass() ???? ou plutot en "make()" ????
    // TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236
    public function build(string $className, array $arguments = [])
    {
        return $this->resolver->build($className, $arguments);
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param callable|string $callback
     * @param array           $parameters
     * @param string|null     $defaultMethod
     *
     * @return mixed
     */
    public function call($callback, array $parameters = [], ?string $defaultMethod = null)
    {
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

    /**
     * {@inheritdoc}
     */
    public function offsetExists($name)
    {
        return $this->has($name) && $this->get($name) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($name, $value)
    {
        $this->add($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($name)
    {
        $this->destroy($name);
    }
}
