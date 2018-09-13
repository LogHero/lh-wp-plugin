<?php
namespace LogHero\Wordpress\Test;
use \LogHero\Client\APIAccessInterface;
use \LogHero\Client\APISettingsInterface;
use \LogHero\Client\LogTransportInterface;
use \LogHero\Client\AsyncFlushFailedException;
use \LogHero\Client\FileLogBuffer;
use \LogHero\Wordpress\LogHeroGlobals;
use \LogHero\Wordpress\LogHeroAPISettings;
use \LogHero\Wordpress\LogHeroPluginClient;
use \LogHero\Wordpress\LogHero_Plugin;
use LogHero\Wordpress\LogHeroPluginSettings;


require_once __DIR__ . '/mock-microtime.php';
require_once __DIR__ . '/../sdk/test/Util.php';


class LogHeroPluginClientTestImpl extends LogHeroPluginClient {
    public function __construct($flushEndpoint = null, $apiAccess = null) {
        parent::__construct($flushEndpoint, $apiAccess);
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
            $this->logHeroTestClient = new LogHeroPluginClientTestImpl(
                '/flush.php',
                $apiAccessStub
            );
            $this->logHeroClient = $this->logHeroTestClient;
        }
    }

    public function onShutdownAction() {
        $this->logHeroClient->submitLogEvent();
    }

    public function onAsyncFlushAction($apiKey) {
        $this->logHeroClient->flush($apiKey);
    }

    public function getFlushTriggerUrl() {
        return $this->flushEndpoint();
    }

}


class LogHeroPluginTest extends \WP_UnitTestCase {
    private $apiKey = 'API_KEY';
    private $plugin;
    private $apiAccessStub;
    private $bufferFileLocation;
    private $settingsFileLocation;
    private $asyncErrorsFilename;
    private $unexpectedErrorsFilename;

    public function setUp() {
        parent::setUp();
        update_option(LogHeroPluginSettings::$apiKeyOptionName, $this->apiKey);
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, false);
        update_option(LogHeroPluginSettings::$disableTransportOptionName, false);
        $this->bufferFileLocation = __DIR__ . '/logs/buffer.loghero.io.txt';
        $this->settingsFileLocation = __DIR__ . '/logs/settings.loghero.io.json';
        $errorFilePrefix = __DIR__ . '/logs/errors.loghero.io';
        $this->asyncErrorsFilename = $errorFilePrefix . '.async-flush.txt';
        $this->unexpectedErrorsFilename = $errorFilePrefix . '.unexpected.txt';
        LogHeroGlobals::Instance()->setLogEventsBufferFilename($this->bufferFileLocation);
        LogHeroGlobals::Instance()->setSettingsStorageFilename($this->settingsFileLocation);
        LogHeroGlobals::Instance()->errors()->setErrorFilenamePrefix($errorFilePrefix);
        $this->apiAccessStub = $this->getMockBuilder(APIAccessInterface::class)->getMock();
        $this->plugin = new LogHero_PluginTestImpl($this->apiAccessStub);
    }

    public function tearDown() {
        remove_action('shutdown', array(LogHero_Plugin::getInstance(), 'sendLogEvent'));
        remove_action('shutdown', array($this->plugin, 'sendLogEvent'));
        if(file_exists($this->bufferFileLocation)) {
            unlink($this->bufferFileLocation);
        }
        if(file_exists($this->settingsFileLocation)) {
            unlink($this->settingsFileLocation);
        }
        if(file_exists($this->asyncErrorsFilename)) {
            unlink($this->asyncErrorsFilename);
        }
        if(file_exists($this->unexpectedErrorsFilename)) {
            unlink($this->unexpectedErrorsFilename);
        }
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
        $this->plugin->onAsyncFlushAction($this->apiKey);
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
        $this->plugin->onAsyncFlushAction($this->apiKey);
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
        $this->plugin->onAsyncFlushAction($this->apiKey);
    }

    public function testIgnoreLogEventsSentByPluginItself() {
        $this->setupServerGlobal('/page-url');
        $_SERVER['HTTP_USER_AGENT'] = LogHeroGlobals::Instance()->getClientId();
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->plugin->onShutdownAction();
        $this->plugin->onAsyncFlushAction($this->apiKey);
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

    /**
     * @expectedException LogHero\Wordpress\InvalidTransportTypeException
     * @expectedExceptionMessage Flush action needs async transport type
     */
    public function testDisableTransportIfConfigured() {
        $this->setupServerGlobal('/page-url');
        $this->fillUpFileLogBuffer();
        update_option(LogHeroPluginSettings::$disableTransportOptionName, true);
        $plugin = new LogHero_PluginTestImpl($this->apiAccessStub);
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $plugin->onShutdownAction();
        $plugin->onAsyncFlushAction($this->apiKey);
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
            "Exception: Some unexpected error occurred!\n",
            LogHeroGlobals::Instance()->errors()->getError('unexpected')
        );
    }

    public function testWriteAsyncFlushFailuresToErrorFile() {
        $logTransport = $this->getMockBuilder(LogTransportInterface::class)->getMock();
        $logTransport->method('submit')
            ->will($this->throwException(new AsyncFlushFailedException("Async flush failed! Message: Flush endpoint returned error!\n STACK TRACE")));
        $this->plugin->logHeroTestClient->setCustomLogTransport($logTransport);
        $this->setupServerGlobal('/page-url');
        $this->plugin->onShutdownAction();
        static::assertFileExists($this->asyncErrorsFilename);
        static::assertEquals(
            "LogHero\Client\AsyncFlushFailedException: Async flush failed! Message: Flush endpoint returned error!\n",
            LogHeroGlobals::Instance()->errors()->getError('async-flush')
        );
    }

    public function testCreateUrlForFlushEndpoint() {
        static::assertEquals('http://example.org/var/www/html/wp-content/plugins/loghero/flush.php', $this->plugin->getFlushTriggerUrl());
    }

    /**
     * @expectedException LogHero\Wordpress\InvalidTokenException
     * @expectedExceptionMessage Token is invalid
     */
    public function testAsyncLogTransportRejectOnFlushIfWrongToken() {
        $this->setupServerGlobal('/page-url');
        $this->plugin->onShutdownAction();
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->plugin->onAsyncFlushAction('INVALID_TOKEN');
    }

    public function testRefreshAPIKeyFromDbIfKeyUndefined() {
        file_put_contents($this->settingsFileLocation, '{}');
        $plugin = new LogHero_PluginTestImpl($this->apiAccessStub);
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
        $plugin->onShutdownAction();
        $plugin->onAsyncFlushAction($this->apiKey);
    }

    public function testInitializeEmptyPluginFromScratch() {
        update_option(LogHeroPluginSettings::$apiKeyOptionName, null);
        file_put_contents($this->settingsFileLocation, '{}');
        new LogHero_PluginTestImpl();
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
