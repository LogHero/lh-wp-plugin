<?php
namespace LogHero\Wordpress;
require_once __DIR__ . '/mock-microtime.php';


function microtime() {
    return 1523429300.8000;
}


class LogHeroClient_PluginTestImpl extends LogHeroClient_Plugin {

    public function __construct($apiAccessStub, $maxRecordSizeInBytes=150) {
        parent::__construct();
        $this->apiClient = new \LogHero\Client\Client(
            $apiAccessStub,
            new \LogHero\Client\MemLogBuffer(150),
            $maxRecordSizeInBytes
        );
    }

}


class LogHeroClientPluginTest extends \WP_UnitTestCase {
    private $plugin;
    private $apiAccessStub;

    function setUp() {
        parent::setUp();
        update_option('api_key', 'API_KEY');
        $this->apiAccessStub = $this->getMockBuilder(\LogHero\Client\APIAccess::class)->getMock();
        $this->plugin = new LogHeroClient_PluginTestImpl($this->apiAccessStub);
    }

    function tearDown() {
        remove_action('shutdown', array(LogHeroClient_Plugin::getInstance(), 'sendLogEvent'));
        remove_action('shutdown', array($this->plugin, 'sendLogEvent'));
    }

	function testSendLogEvent() {
        $this->setupServerGlobal('/page-url');
        $this->apiAccessStub
            ->expects($this->once())
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
                'Firefox'
            ]])));
        $this->plugin->sendLogEvent();
	}

	function testSendLogEventWithoutPageLoadTimeIfNoRequestTime() {
        $this->setupServerGlobal('/page-url');
        $_SERVER['REQUEST_TIME_FLOAT'] = null;
        $this->apiAccessStub
            ->expects($this->once())
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
                'Firefox'
            ]])));
        $this->plugin->sendLogEvent();
    }

    function testSendLogEventsInBatch() {
        remove_action('shutdown', array($this->plugin, 'sendLogEvent'));
        $this->plugin = new LogHeroClient_PluginTestImpl($this->apiAccessStub, 151);
        $this->apiAccessStub
            ->expects($this->once())
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
                    'Firefox'
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
                    'Firefox'
                ]
            ])));
        $this->setupServerGlobal('/page-url-1');
        $this->plugin->sendLogEvent();
        $this->setupServerGlobal('/page-url-2');
        $this->plugin->sendLogEvent();
    }

    private function buildExpectedPayload($rows) {
        return json_encode(array(
            'columns' => ['cid', 'hostname', 'landingPage', 'method', 'statusCode', 'timestamp', 'pageLoadTime', 'ip', 'ua'],
            'rows' => $rows
        ));
    }

    private function setupServerGlobal($pageUrl) {
        $_SERVER['REQUEST_URI'] = $pageUrl;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_USER_AGENT'] = 'Firefox';
        $_SERVER['REQUEST_TIME_FLOAT'] = 1523429298.4109;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        http_response_code(301);
    }
}
