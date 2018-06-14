<?php
namespace LogHero\Wordpress;


use LogHero\Client\APIKeyMemStorage;
use LogHero\Client\APIKeyUndefinedException;

require_once __DIR__ . '/mock-microtime.php';


class LogHero_PluginTestImpl extends LogHero_Plugin {

    public function __construct(\LogHero\Client\APIAccessInterface $apiAccessStub = null) {
        parent::__construct();
        if ($apiAccessStub) {
            $this->logHeroClient = new \LogHero\Wordpress\LogHeroPluginClient(
                '/flush.php',
                $apiAccessStub
            );
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
    private $apiKeyFileLocation;

    public function setUp() {
        parent::setUp();
        update_option('api_key', $this->apiKey);
        $this->bufferFileLocation = __DIR__ . '/logs/buffer.loghero.io.txt';
        $this->apiKeyFileLocation = __DIR__ . '/logs/key.loghero.io.txt';
        LogHeroGlobals::Instance()->setLogEventsBufferFilename($this->bufferFileLocation);
        LogHeroGlobals::Instance()->setAPIKeyStorageFilename($this->apiKeyFileLocation);
        $this->apiKeyStorage = new \LogHero\Client\APIKeyMemStorage();
        $this->apiAccessStub = $this->getMockBuilder(\LogHero\Client\APIAccessInterface::class)->getMock();
        $this->plugin = new LogHero_PluginTestImpl($this->apiAccessStub);
    }

    public function tearDown() {
        remove_action('shutdown', array(LogHero_Plugin::getInstance(), 'sendLogEvent'));
        remove_action('shutdown', array($this->plugin, 'sendLogEvent'));
        if(file_exists($this->bufferFileLocation)) {
            unlink($this->bufferFileLocation);
        }
        if(file_exists($this->apiKeyFileLocation)) {
            unlink($this->apiKeyFileLocation);
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
                '/page-url',
                'POST',
                301,
                '2018-04-11T06:48:18+00:00',
                2389,
                'f528764d624db129b32c21fbca0cb8d6',
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
                '/page-url',
                'POST',
                301,
                '2018-04-11T06:48:20+00:00',
                null,
                'f528764d624db129b32c21fbca0cb8d6',
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
                    '/page-url-1',
                    'POST',
                    301,
                    '2018-04-11T06:48:18+00:00',
                    2389,
                    'f528764d624db129b32c21fbca0cb8d6',
                    'Firefox',
                    'https://www.loghero.io'
                ],
                [
                    'd113ff3141723d50fec2933977c89ea6',
                    'example.org',
                    '/page-url-2',
                    'POST',
                    301,
                    '2018-04-11T06:48:18+00:00',
                    2389,
                    'f528764d624db129b32c21fbca0cb8d6',
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
        LogHeroPluginClient::refreshAPIKey(null);
        $plugin = new LogHero_PluginTestImpl($this->apiAccessStub);
        $this->setupServerGlobal('/page-url');
        $this->apiAccessStub
            ->expects(static::once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload([[
                'd113ff3141723d50fec2933977c89ea6',
                'example.org',
                '/page-url',
                'POST',
                301,
                '2018-04-11T06:48:18+00:00',
                2389,
                'f528764d624db129b32c21fbca0cb8d6',
                'Firefox',
                'https://www.loghero.io'
            ]])));
        $plugin->onShutdownAction();
        $plugin->onAsyncFlushAction($this->apiKey);
    }

    public function testInitializeEmptyPluginFromScratch() {
        update_option('api_key', null);
        LogHeroPluginClient::refreshAPIKey(null);
        new LogHero_PluginTestImpl();
    }

    private function buildExpectedPayload($rows) {
        return json_encode(array(
            'columns' => [
                'cid',
                'hostname',
                'landingPage',
                'method',
                'statusCode',
                'timestamp',
                'pageLoadTime',
                'ip',
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
}
