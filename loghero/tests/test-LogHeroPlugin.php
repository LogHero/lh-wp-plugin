<?php
namespace LogHero\Wordpress\Test;
use \LogHero\Client\APIAccessInterface;
use \LogHero\Client\APISettingsInterface;
use \LogHero\Client\LogTransportInterface;
use \LogHero\Client\FileLogBuffer;
use \LogHero\Wordpress\LogHeroGlobals;
use \LogHero\Wordpress\LogHeroAPISettings;
use \LogHero\Wordpress\LogHeroPluginClient;
use \LogHero\Wordpress\LogHero_Plugin;
use LogHero\Wordpress\LogHeroPluginSettings;
use LogHero\Wordpress\LogHeroUpgrade;
use LogHero\Wordpress\WordPressDeferredLogTransport;


require_once __DIR__ . '/mock-microtime.php';
require_once __DIR__ . '/../sdk/test/Util.php';


class LogHeroPluginClientTestImpl extends LogHeroPluginClient {
    public function __construct($apiAccess = null) {
        parent::__construct($apiAccess);
    }

    public function getLogTransport() {
        return $this->logTransport;
    }

    public function setCustomLogTransport($logTransport) {
        $this->logTransport = $logTransport;
    }
}


class LogHero_PluginTestImpl extends LogHero_Plugin {
    public $logHeroTestClient;

    public function __construct(APIAccessInterface $apiAccessStub = null) {
        parent::__construct();
        if ($apiAccessStub) {
            $this->logHeroTestClient = new LogHeroPluginClientTestImpl($apiAccessStub);
            $this->logHeroClient = $this->logHeroTestClient;
        }
    }

    public function onShutdownAction() {
        $this->logHeroClient->submitLogEvent();
    }

    public function onCronFlushAction() {
        $this->logHeroClient->flushBuffer();
    }

}


class LogHeroPluginTest extends \WP_UnitTestCase {
    private $apiKey = 'API_KEY';
    private $plugin;
    private $apiAccessStub;
    private $bufferFileLocation;
    private $storageDirectory;
    private $flushErrorsFilename;
    private $unexpectedErrorsFilename;

    public function set_up() {
        parent::set_up();
        update_option(LogHeroPluginSettings::$apiKeyOptionName, $this->apiKey);
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, false);
        update_option(LogHeroPluginSettings::$disableTransportOptionName, false);
        $this->storageDirectory = __DIR__ . '/logs';
        LogHeroGlobals::Instance()->setStorageDirectory($this->storageDirectory);
        $this->bufferFileLocation = $this->storageDirectory . '/buffer.test.txt';
        $errorFilePrefix = $this->storageDirectory . '/errors.test';
        $this->flushErrorsFilename = $errorFilePrefix . '.deferred-flush.txt';
        $this->unexpectedErrorsFilename = $errorFilePrefix . '.unexpected.txt';
        LogHeroGlobals::Instance()->setLogEventsBufferFilename($this->bufferFileLocation);
        LogHeroGlobals::Instance()->errors()->setErrorFilenamePrefix($errorFilePrefix);
        $this->apiAccessStub = $this->getMockBuilder(APIAccessInterface::class)->getMock();
        $this->plugin = new LogHero_PluginTestImpl($this->apiAccessStub);
    }

    public function tear_down() {
        remove_action('shutdown', array($this->plugin, 'onShutdown'));
        wp_clear_scheduled_hook(WordPressDeferredLogTransport::$flushCronHook);
        if(file_exists($this->bufferFileLocation)) {
            unlink($this->bufferFileLocation);
        }
        if(file_exists($this->flushErrorsFilename)) {
            unlink($this->flushErrorsFilename);
        }
        if(file_exists($this->unexpectedErrorsFilename)) {
            unlink($this->unexpectedErrorsFilename);
        }
        parent::tear_down();
    }

    public function testNoSendOnSubmit() {
        $this->setupServerGlobal('/page-url');
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->plugin->onShutdownAction();
    }

    public function testSubmitLogEventOnFlush() {
        $this->setupServerGlobal('/page-url');
        $this->apiAccessStub
            ->expects(static::once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload([[
                'd113ff3141723d50fec2933977c89ea6',
                'example.org',
                'http',
                '/page-url',
                'POST',
                301,
                '2018-04-11T06:48:18+00:00',
                2389,
                'f528764d624db129b32c21fbca0cb8d6',
                'ec5decca5ed3d6b8079e2e7e7bacc9f2.cfcd208495d565ef66e7dff9f98764da.cfcd208495d565ef66e7dff9f98764da.c4ca4238a0b923820dcc509a6f75849b',
                'Firefox',
                'https://www.loghero.io'
            ]])));
        $this->plugin->onShutdownAction();
        $this->plugin->onCronFlushAction();
	}

    public function testSubmitLogEventWithoutPageLoadTimeIfNoRequestTime() {
        $this->setupServerGlobal('/page-url');
        $_SERVER['REQUEST_TIME_FLOAT'] = null;
        $this->apiAccessStub
            ->expects(static::once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload([[
                'd113ff3141723d50fec2933977c89ea6',
                'example.org',
                'http',
                '/page-url',
                'POST',
                301,
                '2018-04-11T06:48:20+00:00',
                null,
                'f528764d624db129b32c21fbca0cb8d6',
                'ec5decca5ed3d6b8079e2e7e7bacc9f2.cfcd208495d565ef66e7dff9f98764da.cfcd208495d565ef66e7dff9f98764da.c4ca4238a0b923820dcc509a6f75849b',
                'Firefox',
                'https://www.loghero.io'
            ]])));
        $this->plugin->onShutdownAction();
        $this->plugin->onCronFlushAction();
    }

    public function testSendLogEventsInBatch() {
        $this->apiAccessStub
            ->expects(static::once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload([
                [
                    'd113ff3141723d50fec2933977c89ea6',
                    'example.org',
                    'http',
                    '/page-url-1',
                    'POST',
                    301,
                    '2018-04-11T06:48:18+00:00',
                    2389,
                    'f528764d624db129b32c21fbca0cb8d6',
                    'ec5decca5ed3d6b8079e2e7e7bacc9f2.cfcd208495d565ef66e7dff9f98764da.cfcd208495d565ef66e7dff9f98764da.c4ca4238a0b923820dcc509a6f75849b',
                    'Firefox',
                    'https://www.loghero.io'
                ],
                [
                    'd113ff3141723d50fec2933977c89ea6',
                    'example.org',
                    'http',
                    '/page-url-2',
                    'POST',
                    301,
                    '2018-04-11T06:48:18+00:00',
                    2389,
                    'f528764d624db129b32c21fbca0cb8d6',
                    'ec5decca5ed3d6b8079e2e7e7bacc9f2.cfcd208495d565ef66e7dff9f98764da.cfcd208495d565ef66e7dff9f98764da.c4ca4238a0b923820dcc509a6f75849b',
                    'Firefox',
                    'https://www.loghero.io'
                ]
            ])));
        $this->setupServerGlobal('/page-url-1');
        $this->plugin->onShutdownAction();
        $this->setupServerGlobal('/page-url-2');
        $this->plugin->onShutdownAction();
        $this->plugin->onCronFlushAction();
    }

    public function testIgnoreLogEventsSentByPluginItself() {
        $this->setupServerGlobal('/page-url');
        $_SERVER['HTTP_USER_AGENT'] = LogHeroGlobals::Instance()->getClientId();
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->plugin->onShutdownAction();
        $this->plugin->onCronFlushAction();
    }

    public function testUseSyncTransportIfConfigured() {
        $this->setupServerGlobal('/page-url');
        $this->fillUpFileLogBuffer();
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, true);
        $plugin = new LogHero_PluginTestImpl($this->apiAccessStub);
        $this->apiAccessStub
            ->expects(static::once())
            ->method('submitLogPackage');
        $plugin->onShutdownAction();
    }

    public function testDisableTransportIfConfigured() {
        $this->setupServerGlobal('/page-url');
        $this->fillUpFileLogBuffer();
        update_option(LogHeroPluginSettings::$disableTransportOptionName, true);
        $plugin = new LogHero_PluginTestImpl($this->apiAccessStub);
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $plugin->onShutdownAction();
        $plugin->onCronFlushAction();
    }

    public function testWriteUnexpectedFailuresToErrorFile() {
        $logTransport = $this->getMockBuilder(LogTransportInterface::class)->getMock();
        $logTransport->method('submit')
            ->will($this->throwException(new \Exception("Some unexpected error occurred!\n STACK TRACE")));
        $this->plugin->logHeroTestClient->setCustomLogTransport($logTransport);
        $this->setupServerGlobal('/page-url');
        $this->plugin->onShutdownAction();
        static::assertFileExists($this->unexpectedErrorsFilename);
        static::assertEquals(
            "Some unexpected error occurred!\n",
            LogHeroGlobals::Instance()->errors()->getError('unexpected')
        );
    }

    public function testAsyncModeNeverCallsTheSiteItself() {
        static::assertInstanceOf(
            WordPressDeferredLogTransport::class,
            $this->plugin->logHeroTestClient->getLogTransport()
        );
        static::assertFalse(class_exists('\\LogHero\\Wordpress\\LogHeroFlushEndpoint'));
        $routes = rest_get_server()->get_routes();
        static::assertArrayNotHasKey('/loghero/v1/flush', $routes);
    }

    public function testAsyncModeSchedulesCronFlushWhenBufferIsDue() {
        if (function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request')) {
            static::markTestSkipped('Flush runs after finishing the request on this SAPI');
        }
        $this->setupServerGlobal('/page-url');
        $this->fillUpFileLogBuffer();
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->plugin->onShutdownAction();
        static::assertNotFalse(wp_next_scheduled(WordPressDeferredLogTransport::$flushCronHook));
    }

    public function testStorageDirectoryIsProtected() {
        static::assertFileExists($this->storageDirectory . '/index.php');
        static::assertFileExists($this->storageDirectory . '/.htaccess');
    }

    public function testDefaultStorageDirectoryIsOutsideThePluginAndUnguessable() {
        LogHeroGlobals::Instance()->setStorageDirectory(null);
        $reflection = new \ReflectionClass(LogHeroGlobals::class);
        $method = $reflection->getMethod('resolveStorageDirectory');
        $method->setAccessible(true);
        $directory = $method->invoke(null);
        static::assertStringNotContainsString(dirname(__DIR__), $directory);
        static::assertMatchesRegularExpression('/loghero-[a-f0-9]{32}$/', $directory);
        LogHeroGlobals::Instance()->setStorageDirectory($this->storageDirectory);
    }

    public function testUpgradeRemovesLegacyPublicFiles() {
        $legacy = LogHeroGlobals::legacyLogsDirectory();
        if (!is_dir($legacy)) {
            mkdir($legacy);
        }
        file_put_contents($legacy . '/settings.loghero.io.json', '{"loghero_api_key":"API_KEY"}');
        file_put_contents($legacy . '/errors.loghero.io.async-flush.txt', 'error');
        file_put_contents($legacy . '/.htaccess', 'Deny from  all');
        file_put_contents($legacy . '/buffer.loghero.io.txt', serialize(\LogHero\Client\Test\createLogEvent('/legacy')) . "\n");
        LogHeroUpgrade::upgrade();
        static::assertDirectoryDoesNotExist($legacy);
        static::assertStringContainsString('/legacy', file_get_contents($this->bufferFileLocation));
    }

    public function testInitializeEmptyPluginFromScratch() {
        update_option(LogHeroPluginSettings::$apiKeyOptionName, null);
        static::assertInstanceOf(LogHero_Plugin::class, new LogHero_PluginTestImpl());
    }

    private function buildExpectedPayload($rows) {
        return json_encode(array(
            'columns' => [
                'cid',
                'hostname',
                'protocol',
                'landingPage',
                'method',
                'statusCode',
                'timestamp',
                'pageLoadTime',
                'ip',
                'ipGroups',
                'ua',
                'referer'
            ],
            'rows' => $rows
        ));
    }

    private function setupServerGlobal($pageUrl) {
        $_SERVER['REQUEST_URI'] = $pageUrl;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_USER_AGENT'] = 'Firefox';
        $_SERVER['REQUEST_TIME_FLOAT'] = 1523429298.4109;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_REFERER'] = 'https://www.loghero.io';
        http_response_code(301);
    }

    private function fillUpFileLogBuffer() {
        $fileLogBuffer = new FileLogBuffer($this->bufferFileLocation);
        while($fileLogBuffer->needsDumping() == false) {
            $fileLogBuffer->push(\LogHero\Client\Test\createLogEvent('/some-path'));
        }
    }

}
