<?
define('STOP_STATISTICS', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
$GLOBALS['APPLICATION']->RestartBuffer();

if( $_POST["root"] > 0
    && $_POST["depth"] > 0
    && $_POST["left"] > 0
    && $_POST["right"] > 0
){

    $comments = new \Ameton\Comments();
    $data = $comments->getChilds(
        (int)$_POST["root"],
        (int)$_POST["depth"],
        (int)$_POST["left"],
        (int)$_POST["right"]
    );

    if(count($data) > 0){
        $GLOBALS['APPLICATION']->RestartBuffer();
        foreach($data as $child){?>
            <div class="comment__childs comment__childs-lvl-<?=$child['UF_DEPTH']?>">
                <div class="comment">
                    <div class="comment__header">
                        <div class="comment__user"><?=$child['UF_AUTHOR']?></div>
                        <div class="comment__date"><?=$child['UF_DATE']?></div>
                    </div>
                    <div class="comment__content"><?=$child['UF_TEXT']?></div>
                    <div class="comment__footer"></div>
                </div>
                <?if($child['CHILDS_COUNT'] > 0){?>
                    <span class="js-comments-hide-replies"
                          data-depth="<?=$child['UF_DEPTH']?>"
                          data-root="<?=$child['UF_ROOT_ID']?>"
                          data-margin-l="<?=$child['UF_LEFT']?>"
                          data-margin-r="<?=$child['UF_RIGHT']?>"
                    >Показать(<?=$child['CHILDS_COUNT']?>)</span>
                <?}?>
            </div>
            <?
        }
    }
}
?>
