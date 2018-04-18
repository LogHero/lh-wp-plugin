<?php
namespace LogHero\Wordpress;


function microtime() {
    return 1523429300.8000;
}


class LogHeroClient_PluginTestImpl extends LogHeroClient_Plugin {

    public function __construct($apiAccessStub) {
        parent::__construct();
        $this->apiClient = new \LHClient($apiAccessStub);
    }

}


class LogHeroClientPluginTest extends \WP_UnitTestCase {
    private $plugin;
    private $apiAccessStub;

    function setUp() {
        parent::setUp();
        update_option('api_key', 'API_KEY');
        $this->apiAccessStub = $this->getMockBuilder(\APIAccess::class)->getMock();
        $this->plugin = new LogHeroClient_PluginTestImpl($this->apiAccessStub);
    }

    function tearDown() {
        remove_action('shutdown', array(LogHeroClient_Plugin::getInstance(), 'sendLogEvent'));
        remove_action('shutdown', array($this->plugin, 'sendLogEvent'));
    }

	function testSendLogEvent() {
        $this->setupServerGlobal();
        $this->apiAccessStub
            ->expects($this->once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload([
                'd113ff3141723d50fec2933977c89ea6',
                'example.org',
                '/page-url',
                'POST',
                301,
                '2018-04-11T06:48:18+00:00',
                2389,
                'f528764d624db129b32c21fbca0cb8d6',
                'Firefox'
            ])));
        $this->plugin->sendLogEvent();
	}

	function testSendLogEventWithoutPageLoadTimeIfNoRequestTime() {
        $this->setupServerGlobal();
        $_SERVER['REQUEST_TIME_FLOAT'] = null;
        $this->apiAccessStub
            ->expects($this->once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload([
                'd113ff3141723d50fec2933977c89ea6',
                'example.org',
                '/page-url',
                'POST',
                301,
                '2018-04-11T06:48:20+00:00',
                null,
                'f528764d624db129b32c21fbca0cb8d6',
                'Firefox'
            ])));
        $this->plugin->sendLogEvent();
    }

    private function buildExpectedPayload($row) {
        return json_encode(array(
            'columns' => ['cid', 'hostname', 'landingPage', 'method', 'statusCode', 'timestamp', 'pageLoadTime', 'ip', 'ua'],
            'rows' => [$row]
        ));
    }

    private function setupServerGlobal() {
        $_SERVER['REQUEST_URI'] = '/page-url';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_USER_AGENT'] = 'Firefox';
        $_SERVER['REQUEST_TIME_FLOAT'] = 1523429298.4109;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        http_response_code(301);
    }
}
