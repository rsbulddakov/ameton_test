<?php
$comments = new \Ameton\Comments();
$comments->buildComments($arResult);

$this->__component->SetResultCacheKeys(["ID", "COMMENTS_NAV"]);
?>
