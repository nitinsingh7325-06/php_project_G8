<?php
/**
 * Application Core Class
 */

namespace App\Core;

class Application
{
    private Container $container;
    private Router $router;
    private Session $session;
    private Database $db;
    private array $services = [];
    private array $middlewares = [];

    public function __construct()
    {
        $this->container = new Container();
        $this->router = new Router();
        $this->session = new Session();
        
        // Initialize database
        $this->db = new Database();
        
        // Register core services
        $this->registerCoreServices();
    }

    private function registerCoreServices(): void
    {
        $this->container->set('session', $this->session);
        $this->container->set('db', $this->db);
        $this->container->set('router', $this->router);
    }

    public function registerServices(array $services): void
    {
        foreach ($services as $service) {
            if (class_exists($service)) {
                $this->container->set($service, new $service($this->container));
                $this->services[] = $service;
            }
        }
    }

    public function registerMiddleware(array $middleware): void
    {
        foreach ($middleware as $mw) {
            if (class_exists($mw)) {
                $this->router->addMiddleware($mw);
                $this->middlewares[] = $mw;
            }
        }
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function registerErrorHandler(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    public function handleError($level, $message, $file, $line): void
    {
        $this->logError($level, $message, $file, $line);
        
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    public function handleException($exception): void
    {
        $this->logException($exception);
        
        $appEnv = getenv('APP_ENV') ?: 'local';
        
        if ($appEnv === 'production') {
            $this->renderProductionError($exception);
        } else {
            $this->renderDevelopmentError($exception);
        }
    }

    public function run(): void
    {
        try {
            $response = $this->router->dispatch(
                $_SERVER['REQUEST_METHOD'],
                $_SERVER['REQUEST_URI'] ?? '/'
            );
            
            $this->sendResponse($response);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    private function sendResponse($response): void
    {
        if (is_string($response)) {
            echo $response;
        } elseif (is_array($response)) {
            header('Content-Type: application/json');
            echo json_encode($response);
        } elseif (is_null($response)) {
            // Do nothing
        }
    }

    private function logError($level, $message, $file, $line): void
    {
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $file,
            $line
        );
        
        error_log($logMessage);
        
        // Also log to file if possible
        $logFile = LOG_PATH . '/error.log';
        if (is_writable(dirname($logFile))) {
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    }

    private function logException($exception): void
    {
        $logMessage = sprintf(
            "[%s] EXCEPTION: %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        error_log($logMessage);
        
        // Also log to file
        $logFile = LOG_PATH . '/exception.log';
        if (is_writable(dirname($logFile))) {
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    }

    private function renderDevelopmentError($exception): void
    {
        if (!headers_sent()) {
            http_response_code(500);
        }
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Application Error</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: #1a1a1a;
                    color: #e0e0e0;
                    padding: 40px;
                    margin: 0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                }
                .error-container {
                    max-width: 800px;
                    width: 100%;
                    background: #2d2d2d;
                    border-radius: 12px;
                    padding: 40px;
                    border-left: 4px solid #ff6b6b;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
                }
                .error-title {
                    color: #ff6b6b;
                    font-size: 24px;
                    margin-top: 0;
                    margin-bottom: 20px;
                }
                .error-message {
                    background: #1a1a1a;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    overflow-x: auto;
                }
                .error-details {
                    color: #a0a0a0;
                    font-size: 14px;
                }
                .error-details strong {
                    color: #e0e0e0;
                }
                .error-trace {
                    background: #1a1a1a;
                    padding: 20px;
                    border-radius: 8px;
                    overflow-x: auto;
                    font-family: 'Courier New', monospace;
                    font-size: 12px;
                    color: #a0a0a0;
                    white-space: pre-wrap;
                    word-break: break-all;
                    margin-top: 20px;
                }
                .error-trace strong {
                    color: #ffd93d;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1 class="error-title">⚠️ Application Error</h1>
                <div class="error-message">
                    <strong><?= htmlspecialchars(get_class($exception)) ?></strong><br>
                    <?= htmlspecialchars($exception->getMessage()) ?>
                </div>
                <div class="error-details">
                    <strong>File:</strong> <?= htmlspecialchars($exception->getFile()) ?><br>
                    <strong>Line:</strong> <?= $exception->getLine() ?><br>
                    <strong>Code:</strong> <?= $exception->getCode() ?>
                </div>
                <div class="error-trace">
                    <strong>Stack Trace:</strong><br>
                    <?= htmlspecialchars($exception->getTraceAsString()) ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    private function renderProductionError($exception): void
    {
        if (!headers_sent()) {
            http_response_code(500);
        }
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Something went wrong</title>
        </head>
        <body>
            <h1>Something went wrong</h1>
            <p>We're working on fixing this issue. Please try again later.</p>
        </body>
        </html>
        <?php
    }
}