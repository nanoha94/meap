<?php

namespace App\Custom\Auth;

use Illuminate\Auth\Passwords\CacheTokenRepository;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Foundation\Application;
use InvalidArgumentException;

class CustomPasswordBrokerManager extends PasswordBrokerManager
{

    /**
     * CustomPasswordBrokerを指定する
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Password resetter [{$name}] is not defined.");
        }

        $app = $this->getApplication();

        // The password broker uses a token repository to validate tokens and send user
        // password e-mails, as well as validating that password reset process as an
        // aggregate service of sorts providing a convenient interface for resets.
        return new CustomPasswordBroker(
            $this->createTokenRepository($config),
            $app['auth']->createUserProvider($config['provider'] ?? null),
            $app['events'] ?? null,
        );
    }

    /**
     * CustomDatabaseTokenRepositoryを指定する
     *
     * @param  array  $config
     * @return \Illuminate\Auth\Passwords\TokenRepositoryInterface
     */
    protected function createTokenRepository(array $config)
    {
        $app = $this->getApplication();
        $key = $app['config']['app.key'];

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        if (isset($config['driver']) && $config['driver'] === 'cache') {
            return new CacheTokenRepository(
                $app['cache']->store($config['store'] ?? null),
                $app['hash'],
                $key,
                ($config['expire'] ?? 60) * 60,
                $config['throttle'] ?? 0,
                $config['prefix'] ?? '',
            );
        }

        return new CustomDatabaseTokenRepository(
            $app['db']->connection($config['connection'] ?? null),
            $app['hash'],
            $config['table'],
            $key,
            $config['expire'],
            $config['throttle'] ?? 0
        );
    }

    /**
     * 配列アクセスできる Laravel アプリケーション実装を返す
     *
     * @return \Illuminate\Foundation\Application
     */
    private function getApplication(): Application
    {
        $app = $this->app;

        if (! $app instanceof Application) {
            throw new InvalidArgumentException('Password broker requires the Laravel application.');
        }

        return $app;
    }
}
