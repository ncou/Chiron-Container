<?php

declare(strict_types=1);

namespace Chiron\Container;

use Chiron\Container\Annotations\Alias;
use Chiron\Container\Exception\CannotFindParameterException;
use Chiron\Container\Exception\CannotResolveException;
use Chiron\Container\Exception\ContainerException;
use Chiron\Container\Exception\EntityNotFoundException;
use Chiron\Container\Reflection\ReflectionCallable;
use Chiron\Container\Definition\DefinitionInterface;
use Chiron\Container\Definition\Definition;
use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionObject;

use Chiron\Container\Definition\Reference;

// https://github.com/thephpleague/container/blob/master/src/Container.php#L92
//https://github.com/mrferos/di/blob/master/src/Container.php#L99

// TODO : vérifier le type de la variable qui est trouvée lorsqu'on résout les arguments/parameters : https://github.com/spiral/core/blob/0ee9848f04b45d09dbea18fa05a0bbda35f3401b/src/Container.php#L610

// TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236

//https://github.com/yiisoft/injector/blob/master/src/Injector.php

//https://github.com/railt/container/blob/1.4.x/src/Container/ParamResolver.php

// TODO : classe à renommer en DefinitionResolver ou en ParamResolver
class ReflectionResolver
{
    protected $container;

    /**
     * Array of entries being resolved. Used to avoid circular dependencies and infinite loops.
     *
     * @var array
     */
    protected $entriesBeingResolved = [];

    // TODO : mettre la valeur par défaut du paramétre à null pour rendre facultatif la présence du container.
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function resolveDefinition(DefinitionInterface $definition)
    {
        $concrete = $definition->getConcrete();
        $parameters = $definition->getAssigns();
        $entryName = $definition->getAlias();

        // Check if we are already getting this entry -> circular dependency
        if (isset($this->entriesBeingResolved[$entryName])) {
            throw new ContainerException(sprintf(
                'Circular dependency detected while trying to resolve entry "%s"',
                $entryName
            ));
        }
        $this->entriesBeingResolved[$entryName] = true;

        // Resolve the definition
        try {
            $value = $this->resolve($concrete, $parameters);
        } finally {
            unset($this->entriesBeingResolved[$entryName]);
        }

        return $value;

    }

    // TODO : exemple ==>    https://github.com/thephpleague/container/blob/master/src/Definition/Definition.php#L189
    // $concrete c'est un mixed
    private function resolve($concrete, array $parameters = [])
    {
        $instance = $concrete;

        // TODO : permettre de passer dans le concrete un tableau avec un nom string de class ou une instance de classe et un second paramétre de type string qui est une méthode privée. Utiliser la reflection ->setAccessible(true) pour permettre d'invoker cette méthode. Cela est utilse lors de la création de "factory" de type ->bind('id', ['class', 'method']) et que la méthode est privée.  ====>  https://github.com/spiral/core/blob/master/src/Container.php#L499

        // TODO : comment ca sez passe si on a mis dans la définition une instance d'une classe qui a une méthode __invoke ???? elle va surement être interprété comme un callable mais ce n'est pas ce qu'on souhaite !!!!
        // TODO : il faudrait ajouter aussi une vérif soit "différent de object", sinon ajouter un if en début de proécédure dans le cas ou c'est un "scalaire ou objet" on n'essaye pas de résoudre la variable $concrete.
        if (is_callable($concrete)) {
            //$concrete = $this->resolveCallable($concrete);
            $instance = $this->callCallable($concrete, $parameters);
        }

        if (is_string($concrete) && class_exists($concrete)) {
            //$concrete = $this->resolveClass($concrete);
            $instance = $this->build($concrete, $parameters);
        }

        //TODO : code à améliorer surtout dans le cas ou il y a des paramétres !!!!!
        if ($concrete instanceof Reference) {
            $instance = $concrete->resolve($this->container, $parameters);
        }

        // could be an instance or a scalar
        return $instance;
    }

    // TODO : ajouter la signature dans l'interface
    // TODO : regarder aussi ici : https://github.com/mrferos/di/blob/master/src/Definition/AbstractDefinition.php#L75
    // TODO : regarder ici pour utiliser le arobase @    https://github.com/slince/di/blob/master/DefinitionResolver.php#L210
    // TODO : améliorer le resolve avec la gestion des classes "Raw" et "Reference" =>   https://github.com/thephpleague/container/blob/91a751faabb5e3f5e307d571e23d8aacc4acde88/src/Argument/ArgumentResolverTrait.php#L17
    public function resolveArguments(array $arguments): array
    {
        foreach ($arguments as &$arg) {
            if (! is_string($arg)) {
                continue;
            }

            //if (! is_null($this->container) && $this->container->has($arg)) {
            if ($this->container->has($arg)) {
                $arg = $this->container->get($arg);

                continue;
            }
        }

        return $arguments;
    }

    // TODO : améliorer le code regarder ici   =>   https://github.com/illuminate/container/blob/master/Container.php#L778
    // TODO : améliorer le code et regarder ici => https://github.com/thephpleague/container/blob/68c148e932ef9959af371590940b4217549b5b65/src/Definition/Definition.php#L225
    // TODO : attention on ne gére pas les alias, alors que cela pourrait servir si on veut builder une classe en utilisant l'alias qui est présent dans le container. Réfléchir si ce cas peut arriver.
    // TODO : renommer en buildClass() ????
    // TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236
    // TODO : renommer la fonction en "make()"
    public function build(string $className, array $arguments = [])
    {
        $arguments = $this->resolveArguments($arguments);

        $class = $this->reflectClass($className);

        // https://github.com/spiral/core/blob/02580dff7f1fcbc5e74caa1f78ea84c0e4c0d92e/src/Container.php#L534
        // https://github.com/spiral/core/blob/02580dff7f1fcbc5e74caa1f78ea84c0e4c0d92e/src/Container.php#L551
        // https://github.com/spiral/core/blob/02580dff7f1fcbc5e74caa1f78ea84c0e4c0d92e/src/Container.php#L558
        // TODO : améliorer ce bout de code, on fait 2 fois un new class, alors qu'on pourrait en faire qu'un !!! https://github.com/illuminate/container/blob/master/Container.php#L815
        if ($constructor = $class->getConstructor()) {
            $arguments = $this->getParameters($constructor, $arguments);

            return new $className(...$arguments);
        }

        //$reflection->newInstanceArgs($resolved);
        return new $className();
    }

    private function reflectClass(string $className): ReflectionClass
    {
        if (! class_exists($className)) {
            throw new InvalidArgumentException("Entry '{$className}' cannot be resolved");
        }

        // TODO : vérifier que le constructeur est public !!!! => https://github.com/PHP-DI/PHP-DI/blob/cdcf21d2a8a60605e81ec269342d48b544d0dfc7/src/Definition/Source/ReflectionBasedAutowiring.php#L31
        // TODO : déplacer ce bout de code dans une méthode "reflectClass()"
        $class = new ReflectionClass($className);

        // Prevent error if you try to instanciate an abstract class or a class with a private constructor.
        if (! $class->isInstantiable()) {
            throw new ContainerException(sprintf(
                'Entry "%s" cannot be resolved: the class is not instantiable',
                $className
            ));
        }

        return $class;
    }

    public function callCallable(callable $callable, array $args = [])
    {
        // TODO : utiliser la méthode call_user_func_array ????
        return $callable($this->container, $args);
    }


    /**
     * Invoke a callable and inject its dependencies.
     *
     * @param callable $callable
     * @param array    $args
     *
     * @return mixed
     */
    //https://github.com/yiisoft/injector/blob/master/src/Injector.php#L69
    public function call(callable $callable, array $args = [])
    {
        $args = $this->resolveArguments($args);

        $reflection = $this->reflectCallable($callable);

        return call_user_func_array(
                $callable,
                $this->getParameters($reflection, $args)
            );
    }

    private function reflectCallable(callable $callee): ReflectionFunctionAbstract
    {
        // closure, or function name,
        if ($callee instanceof Closure) {
            return new ReflectionFunction($callee);
        } elseif (is_string($callee) && strpos($callee, '::') === false) {
            return new ReflectionFunction($callee);
        }
        if (is_string($callee)) {
            $callee = explode('::', $callee);
        } elseif (is_object($callee)) {
            $callee = [$callee, '__invoke'];
        }
        if (is_object($callee[0])) {
            $reflection = new ReflectionObject($callee[0]);
            if ($reflection->hasMethod($callee[1])) {
                return $reflection->getMethod($callee[1]);
            }
            //magicMethod
            return $reflection->getMethod('__call');
        }

        $reflection = new ReflectionClass($callee[0]);
        if ($reflection->hasMethod($callee[1])) {
            return $reflection->getMethod($callee[1]);
        }
        //magicMethod
        return $reflection->getMethod('__callStatic');
    }

    /*
        public function call_save(callable $callable, array $args = [])
        {

            if (is_string($callable) && strpos($callable, '::') !== false) {
                $callable = explode('::', $callable);
            }
            if (is_array($callable)) {
                if (is_string($callable[0])) {
                    $callable[0] = $this->container->get($callable[0]);
                }
                $reflection = new ReflectionMethod($callable[0], $callable[1]);
                if ($reflection->isStatic()) {
                    $callable[0] = null;
                }
                return $reflection->invokeArgs($callable[0], $this->getParameters($reflection, $args));
            }
            if (is_object($callable)) {
                $reflection = new ReflectionMethod($callable, '__invoke');
                return $reflection->invokeArgs($callable, $this->getParameters($reflection, $args));
            }
            $reflection = new ReflectionFunction($callable);

            return $reflection->invokeArgs($this->getParameters($reflection, $args));
        }
    */

    /**
     * @param \ReflectionFunctionAbstract $reflection
     * @param array                       $arguments
     *
     * @return array
     */
    // TODO : renommer en getMethodDependencies() ou plutot en reflectArguments(ReflectionFunctionAbstract $method, array $args = []) : array ou alors en resolveFunctionArguments()
    protected function getParameters(ReflectionFunctionAbstract $reflection, array $arguments = []): array
    {
        // TODO : améliorer ce bout de code ******************
        $parametersToReturn = static::getSeqArray($arguments); // utiliser plutot ce bout de code pour éviter d'initialiser un tableau lorsque les clés sont numeriques => https://github.com/illuminate/container/blob/master/BoundMethod.php#L119

        $reflectionParameters = array_slice($reflection->getParameters(), count($parametersToReturn));

        if (! count($reflectionParameters)) {
            return $parametersToReturn;
        }
        // TODO END ******************************************

        /* @var \ReflectionParameter $param */
        foreach ($reflectionParameters as $param) {
            /*
             * #1. search in arguments by parameter name
             * #1.1. search in arguments by class name
             * #2. if parameter has type hint
             * #2.1. search in container by class name
             * #3. if has default value, insert default value.
             * #4. exception
             */
            $paramName = $param->getName();

            try {
                if (array_key_exists($paramName, $arguments)) { // #1.
                    $parametersToReturn[] = $arguments[$paramName];

                    continue;
                }

                $paramClass = $param->getClass();

                if ($paramClass) { // #2.
                    $paramClassName = $paramClass->getName();

                    if (array_key_exists($paramClassName, $arguments)) {
                        $parametersToReturn[] = $arguments[$paramClassName];

                        continue;
                    } else { // #2.1.
                        try {
                            // TODO : on devrait pas créer une méthode make() qui soit un alias de get ? => https://github.com/illuminate/container/blob/master/Container.php#L616
                            // TODO : https://github.com/illuminate/container/blob/master/Container.php#L925
                            // TODO : ajouter des tests dans le cas ou la classe passée en parameter est optionnelle (cad avec une valeur par défaut), il faudrait aussi faire un test avec "?ClassObject" voir si on passe null par défaut ou si on léve une exception car la classe n'existe pas !!!! => https://github.com/illuminate/container/blob/master/Container.php#L935
                            $parametersToReturn[] = $this->container->get($paramClassName);

                            continue;
                        } catch (EntityNotFoundException $e) {
                        }
                    }
                }
                if ($param->isDefaultValueAvailable()) { // #3.
                    $parametersToReturn[] = $param->getDefaultValue();

                    continue;
                }


                // TODO : à regrouper dans une classe ArgumentException
                $name = $reflection->getName();
                if ($reflection instanceof ReflectionMethod) {
                    $name = $reflection->class . '::' . $name;
                }
                throw new ContainerException("Parameter '{$paramName}' cannot be resolved in '{$name}'"); // #4.
                // TODO -- END
            } catch (ReflectionException $e) {
                // ReflectionException is thrown when the class doesn't exist.
                throw new ContainerException("Parameter '{$paramName}' cannot be resolved");
            }
        }

        return $parametersToReturn;
    }

    /**
     * @param array $array
     *
     * @return array
     */
    // TODO : essayer ce bout de code pour améliorer les choses : https://github.com/slince/di/blob/master/DefinitionResolver.php#L159
    protected static function getSeqArray(array $array): array
    {
        $arrayToReturn = [];
        foreach ($array as $key => $item) {
            if (is_int($key)) {
                $arrayToReturn[] = $item;
            }
        }

        return $arrayToReturn;
    }
}
