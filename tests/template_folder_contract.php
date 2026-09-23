<?php
spl_autoload_register(function ($name) {
    $prefix = 'Sendgo\\Php\\';
    if (str_starts_with($name, $prefix)) require __DIR__.'/../src/'.str_replace('\\', '/', substr($name, strlen($prefix))).'.php';
});
use Sendgo\Php\Sendgo;
use Sendgo\Php\Exception\SendgoException;
$c = new Sendgo(['access_key' => 'test-access', 'secret_key' => 'test-secret', 'api_version' => 'v2', 'url' => getenv('SENDGO_TEST_URL')]);
$f = '11111111-1111-4111-8111-111111111111';
$key = '채널 /?';
// Laravel 파사드용 메서드와 프로퍼티가 같은 서비스를 반환해야 합니다.
if ($c->templateFolders() !== $c->templateFolders || $c->template_folders() !== $c->templateFolders) throw new RuntimeException('파사드 접근 오류');
$c->templateFolders->list();
$c->templateFolders->list(['templateType' => 'brand', 'kakaoSenderKey' => $key]);
$c->templateFolders->create(['name' => '주문']);
$c->templateFolders->create(['name' => '하위', 'parentUuid' => $f]);
foreach (['notice', 'brand'] as $type) {
    $c->templateFolders->assign(['templateType' => $type, 'kakaoSenderKey' => $key, 'templateCodes' => ['코드 1', 'code/2'], 'folderUuid' => $f]);
    $c->templateFolders->assign(['templateType' => $type, 'kakaoSenderKey' => $key, 'templateCodes' => ['코드 1'], 'folderUuid' => null]);
}
$c->noticeTemplates->list(['folderUuid' => 'none']);
$c->brandTemplates->list(['folderUuid' => $f]);
$c->noticeTemplates->create(['templateName' => '테스트', 'folderUuid' => $f]);
$c->brandTemplates->create(['templateName' => '테스트', 'folderUuid' => $f]);
foreach ([['forbidden',403,'ACCESS_KEY_NOT_APPROVED'],['invalid',422,'VALIDATION_FAILED'],['missing',404,'TEMPLATE_FOLDER_NOT_FOUND']] as [$type,$status,$code]) {
    try { $c->templateFolders->list(['templateType' => $type]); throw new RuntimeException('오류가 발생하지 않음'); }
    catch (SendgoException $e) { if ($e->getStatusCode() !== $status || $e->getErrorCode() !== $code) throw $e; }
}
