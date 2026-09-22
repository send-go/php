<?php
spl_autoload_register(function ($name) {
    $prefix = 'Sendgo\\Php\\';
    if (str_starts_with($name, $prefix)) require __DIR__.'/../src/'.str_replace('\\', '/', substr($name, strlen($prefix))).'.php';
});
use Sendgo\Php\AccountClient;
use Sendgo\Php\Exception\SendgoException;
$url = getenv('SENDGO_TEST_URL').'/';
try { new AccountClient(''); throw new RuntimeException('빈 토큰 허용'); } catch (InvalidArgumentException $e) {}
$c = new AccountClient('test-agent', $url);
function check($data) { if (($data['message'] ?? '') !== 'Success') throw new RuntimeException('응답 오류'); }
check($c->me());
check($c->organizations());
check($c->selectOrganization(null));
check($c->selectOrganization("team-id"));
check($c->apiKeys());
check($c->createApiKey(["name" => "한글 이름", "ipAddresses" => [["ip" => "192.0.2.1", "description" => "서버"]]]));
check($c->apiKey("key/id ?"));
check($c->updateApiKey("key/id ?", "새 이름"));
check($c->deleteApiKey("key/id ?"));
check($c->issueToken("key/id ?"));
check($c->allowedIps("key/id ?"));
check($c->addAllowedIp("key/id ?", ["ip" => "192.0.2.1", "description" => "서버"]));
check($c->deleteAllowedIp("key/id ?", "ip/id ?"));
foreach ([['expired', 401, 'AGENT_TOKEN_EXPIRED'], ['forbidden', 403, 'AGENT_ABILITY_MISSING']] as [$token, $status, $code]) {
    try { (new AccountClient($token, $url))->me(); throw new RuntimeException('오류가 발생하지 않음'); }
    catch (SendgoException $e) { if ($e->getStatusCode() !== $status || $e->getErrorCode() !== $code) throw $e; }
}
