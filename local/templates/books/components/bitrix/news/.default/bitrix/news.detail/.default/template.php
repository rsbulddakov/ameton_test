<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
CJSCore::Init(array("jquery"));

?>
<div class="news-detail">
	<?if($arParams["DISPLAY_PICTURE"]!="N" && is_array($arResult["DETAIL_PICTURE"])):?>
		<img
			class="detail_picture"
			border="0"
			src="<?=$arResult["DETAIL_PICTURE"]["SRC"]?>"
			width="<?=$arResult["DETAIL_PICTURE"]["WIDTH"]?>"
			height="<?=$arResult["DETAIL_PICTURE"]["HEIGHT"]?>"
			alt="<?=$arResult["DETAIL_PICTURE"]["ALT"]?>"
			title="<?=$arResult["DETAIL_PICTURE"]["TITLE"]?>"
			/>
	<?endif?>
	<?if($arParams["DISPLAY_DATE"]!="N" && $arResult["DISPLAY_ACTIVE_FROM"]):?>
		<span class="news-date-time"><?=$arResult["DISPLAY_ACTIVE_FROM"]?></span>
	<?endif;?>
	<?if($arParams["DISPLAY_NAME"]!="N" && $arResult["NAME"]):?>
		<h3><?=$arResult["NAME"]?></h3>
	<?endif;?>
	<?if($arParams["DISPLAY_PREVIEW_TEXT"]!="N" && $arResult["FIELDS"]["PREVIEW_TEXT"]):?>
		<p><?=$arResult["FIELDS"]["PREVIEW_TEXT"];unset($arResult["FIELDS"]["PREVIEW_TEXT"]);?></p>
	<?endif;?>
	<?if($arResult["NAV_RESULT"]):?>
		<?if($arParams["DISPLAY_TOP_PAGER"]):?><?=$arResult["NAV_STRING"]?><br /><?endif;?>
		<?echo $arResult["NAV_TEXT"];?>
		<?if($arParams["DISPLAY_BOTTOM_PAGER"]):?><br /><?=$arResult["NAV_STRING"]?><?endif;?>
	<?elseif($arResult["DETAIL_TEXT"] <> ''):?>
		<?echo $arResult["DETAIL_TEXT"];?>
	<?else:?>
		<?echo $arResult["PREVIEW_TEXT"];?>
	<?endif?>
	<div style="clear:both"></div>
	<br />
	<?foreach($arResult["FIELDS"] as $code=>$value):
		if ('PREVIEW_PICTURE' == $code || 'DETAIL_PICTURE' == $code)
		{
			?><?=GetMessage("IBLOCK_FIELD_".$code)?>:&nbsp;<?
			if (!empty($value) && is_array($value))
			{
				?><img border="0" src="<?=$value["SRC"]?>" width="<?=$value["WIDTH"]?>" height="<?=$value["HEIGHT"]?>"><?
			}
		}
		else
		{
			?><?=GetMessage("IBLOCK_FIELD_".$code)?>:&nbsp;<?=$value;?><?
		}
		?><br />
	<?endforeach;
	foreach($arResult["DISPLAY_PROPERTIES"] as $pid=>$arProperty):?>

		<?=$arProperty["NAME"]?>:&nbsp;
		<?if(is_array($arProperty["DISPLAY_VALUE"])):?>
			<?=implode("&nbsp;/&nbsp;", $arProperty["DISPLAY_VALUE"]);?>
		<?else:?>
			<?=$arProperty["DISPLAY_VALUE"];?>
		<?endif?>
		<br />
	<?endforeach;
	if(array_key_exists("USE_SHARE", $arParams) && $arParams["USE_SHARE"] == "Y")
	{
		?>
		<div class="news-detail-share">
			<noindex>
			<?
			$APPLICATION->IncludeComponent("bitrix:main.share", "", array(
					"HANDLERS" => $arParams["SHARE_HANDLERS"],
					"PAGE_URL" => $arResult["~DETAIL_PAGE_URL"],
					"PAGE_TITLE" => $arResult["~NAME"],
					"SHORTEN_URL_LOGIN" => $arParams["SHARE_SHORTEN_URL_LOGIN"],
					"SHORTEN_URL_KEY" => $arParams["SHARE_SHORTEN_URL_KEY"],
					"HIDE" => $arParams["SHARE_HIDE"],
				),
				$component,
				array("HIDE_ICONS" => "Y")
			);
			?>
			</noindex>
		</div>
		<?
	}
	?>
</div>
<?php if(count($arResult['COMMENTS']) > 0){
    foreach ($arResult['COMMENTS'] as $item) {?>
        <div class="comments__item">
            <div class="comment">
                <div class="comment__header">
                    <div class="comment__user">
                        <?=$item['UF_AUTHOR']?>
                    </div>
                    <div class="comment__date">
                        <?=$item['UF_DATE']?>
                    </div>
                </div>
                <div class="comment__content">
                    <?=$item['UF_TEXT']?>
                </div>
                <div class="comment__footer"></div>
            </div>
            <?if($item['CHILDS_COUNT'] > 0){?>
                <? foreach ($item['CHILDS'] as $child) {?>
                    <div class="comment__childs comment__childs-lvl-2">
                        <div class="comment">
                            <div class="comment__header">
                                <div class="comment__user">
                                    <?=$child['UF_AUTHOR']?>
                                </div>
                                <div class="comment__date">
                                    <?=$child['UF_DATE']?>
                                </div>
                            </div>
                            <div class="comment__content">
                                <?=$child['UF_TEXT']?>
                            </div>
                            <div class="comment__footer"></div>
                        </div>
                        <?if($child['CHILDS_COUNT'] > 0){?>
                            <span class="js-comments-hide-replies"
                                  data-depth="<?=$child['UF_DEPTH']?>"
                                  data-root="<?=$child['UF_ROOT_ID']?>"
                                  data-margin-l="<?=$child['UF_LEFT']?>"
                                  data-margin-r="<?=$child['UF_RIGHT']?>"
                            >
                                Показать(<?=$child['CHILDS_COUNT']?>)
                            </span>
                        <?}?>
                    </div>
                <?}?>
            <?}?>
        </div>
    <?}
} ?>
<?php
$APPLICATION->IncludeComponent("bitrix:main.pagenavigation", "",
    Array(
        "NAV_OBJECT" => $arResult['COMMENTS_NAV'],
        "SEF_MODE" => "N"
    ),
    false
);
?>
<script>
    $( document ).ready(function() {
        $('body').on("click", ".js-comments-hide-replies", function() {
            var btn = $(this);
            $.ajax({
                url: "/ajax/comments.php",
                type: "POST",
                dataType: "html",
                data: {
                    depth: $(btn).attr('data-depth'),
                    left: $(btn).attr('data-margin-l'),
                    right: $(btn).attr('data-margin-r'),
                    root: $(btn).attr('data-root'),
                },
                success: function (data) {
                    $(btn).before(data);
                    $(btn).hide();
                }
            });
        });
    });
</script>

