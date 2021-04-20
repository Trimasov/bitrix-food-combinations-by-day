<?
define("NEED_AUTH", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("");
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
?>

<?
$arrSection=[6,7,8,9,10];
$IBLOCK_ID=7;
$days = 3;
$cals = 2500;
$proteins = 124;
$proteins_min = $proteins * 0.9;
$proteins_max = $proteins * 1.1;
$fats = 65;
$fats_min = $fats * 0.9;
$fats_max = $fats * 1.1;
$carbo = 194;
$carbo_min = $carbo * 0.9;
$carbo_max = $carbo * 1.1;
$cals_priem = $cals / 5;

$arrAll=[];
foreach($arrSection as $section){
    $arSelect = Array("ID", "NAME");
    $arFilter = Array("IBLOCK_ID"=>$IBLOCK_ID, "SECTION_ID"=>$section);
    $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
    while($ob = $res->GetNextElement())
    {
        $arFields = $ob->GetFields();
        $name_razdela = CIBlockSection::GetByID($section);
        if($ar_res = $name_razdela->GetNext()){
            //echo 'Имя раздела - '.$ar_res['NAME'].'<br/>';
        }
        //echo 'id-раздела - '.$section.'<br/>';
        //echo 'Имя блюда - '.$arFields['NAME'].'<br/>';
        //$arrAll[$ar_res['NAME']][$arFields['ID']]=$arFields['NAME'];

        $db_props = CIBlockElement::GetProperty($IBLOCK_ID, $arFields['ID'], "sort", "asc", array());
        /*Перечисляем все его свойства*/
        while($ar_props = $db_props->Fetch()){
            /*Выводим все параметры данного свойства*/
            if($ar_props['NAME']=='Ккал'){
                $raschet_vesa = ($cals_priem / $ar_props['VALUE']);
                $ves_za_priem = intval($raschet_vesa * 100);

                // округление до ближайшего числа кратного 5
                $ves_za_priem = (round($ves_za_priem)%5 === 0) ? round($ves_za_priem) : round(($ves_za_priem+5/2)/5)*5;
                //echo 'нужный вес - '.$ves_za_priem.' для набора '.$cals_priem.' каллорий <br/>';
                $arrAll[$ar_res['NAME']][$arFields['NAME']]['ves']=$ves_za_priem;
                $arrAll[$ar_res['NAME']][$arFields['NAME']]['cals']=$cals_priem;
            }
            elseif ($ar_props['NAME']=='Б'){
                $summ_proteins = $ar_props['VALUE'] * $raschet_vesa;
                $proteins_za_priem = intval($summ_proteins);
                //echo 'Нужные белки - '.$proteins_za_priem.'<br/>';
                $arrAll[$ar_res['NAME']][$arFields['NAME']]['protein']=$proteins_za_priem;
            }
            elseif ($ar_props['NAME']=='Ж'){
                $summ_fats = $ar_props['VALUE'] * $raschet_vesa;
                $fats_za_priem = intval($summ_fats);
                //echo 'Нужные жиры - '.$fats_za_priem.'<br/>';
                $arrAll[$ar_res['NAME']][$arFields['NAME']]['fat']=$fats_za_priem;
            }
            elseif ($ar_props['NAME']=='У'){
                $summ_carbo = $ar_props['VALUE'] * $raschet_vesa;
                $carbo_za_priem = intval($summ_carbo);
                //echo 'Нужные углеводы - '.$carbo_za_priem.'<br/>';
                $arrAll[$ar_res['NAME']][$arFields['NAME']]['carbo']=$carbo_za_priem;
            }
        }
    }

}

function podbor($arrAll, $i1=0, $i2=0, $i3=0, $i4=0, $i5=0,
                $proteins_min, $proteins_max, $fats_min, $fats_max, $carbo_min, $carbo_max, $days, $current_day) {

    $protein_sum = 0;
    $fats_sum = 0;
    $carbo_sum = 0;
    $i1_count = 0;
    $i2_count = 0;
    $i3_count = 0;
    $i4_count = 0;
    $i5_count = 0;

    foreach($arrAll as $dinner => $portions){
        $arr = array_values($portions);
        foreach($arr as $key => $portion){
            //узнаем количество блюд в каждом из приемов пищи
            if($dinner=='Завтрак'){$i1_count++; $name_dinner1 = array_keys($portions);}
            if($dinner=='Перекус 1'){$i2_count++; $name_dinner2 = array_keys($portions);}
            if($dinner=='Обед'){$i3_count++; $name_dinner3 = array_keys($portions);}
            if($dinner=='Перекус 2'){$i4_count++; $name_dinner4 = array_keys($portions);}
            if($dinner=='Ужин'){$i5_count++; $name_dinner5 = array_keys($portions);}

            if($dinner=='Завтрак' && $key != $i1 ){
                continue;
            }
            if($dinner=='Перекус 1' && $key != $i2 ){
                continue;
            }
            if($dinner=='Обед' && $key != $i3 ){
                continue;
            }
            if($dinner=='Перекус 2' && $key != $i4 ){
                continue;
            }
            if($dinner=='Ужин' && $key != $i5 ){
                continue;
            }
            $protein_sum += $portion['protein'];
            $fats_sum += $portion['fat'];
            $carbo_sum += $portion['carbo'];
        }
    }
    if(
        ($protein_sum >= $proteins_min && $protein_sum <= $proteins_max) &&
        ($fats_sum >= $fats_min && $fats_sum <= $fats_max) &&
        ($carbo_sum >= $carbo_min && $carbo_sum <= $carbo_max)
    )
        {

            if($days > $current_day){
                $current_day++;
                if ($i5_count > $i5){
                    $i5++;
                }
                if ($i5_count == $i5){
                    $i5=1;
                    $i4++;
                }
                if ($i4_count == $i4){
                    $i4=1;
                    $i3++;
                }
                if ($i3_count == $i3){
                    $i3=1;
                    $i2++;
                }
                if ($i2_count == $i2){
                    $i2=1;
                    $i1++;
                }
                $returnArr = podbor($arrAll, $i1, $i2, $i3, $i4, $i5,
                    $proteins_min, $proteins_max, $fats_min, $fats_max, $carbo_min, $carbo_max, $days, $current_day);
                    return array_merge($returnArr,[[
                        array_merge_recursive(array_values($arrAll['Завтрак'])[$i1],['name'=>$name_dinner1[$i1]]),
                        array_merge_recursive(array_values($arrAll['Перекус 1'])[$i2],['name'=>$name_dinner2[$i2]]),
                        array_merge_recursive(array_values($arrAll['Обед'])[$i3],['name'=>$name_dinner3[$i3]]),
                        array_merge_recursive(array_values($arrAll['Перекус 2'])[$i4],['name'=>$name_dinner4[$i4]]),
                        array_merge_recursive(array_values($arrAll['Ужин'])[$i5],['name'=>$name_dinner5[$i5]])
                    ]]);

            }else{
                //возвращаем результат перебора, если все норм
                return([[
                    array_merge_recursive(array_values($arrAll['Завтрак'])[$i1],['name'=>$name_dinner1[$i1]]),
                    array_merge_recursive(array_values($arrAll['Перекус 1'])[$i2],['name'=>$name_dinner2[$i2]]),
                    array_merge_recursive(array_values($arrAll['Обед'])[$i3],['name'=>$name_dinner3[$i3]]),
                    array_merge_recursive(array_values($arrAll['Перекус 2'])[$i4],['name'=>$name_dinner4[$i4]]),
                    array_merge_recursive(array_values($arrAll['Ужин'])[$i5],['name'=>$name_dinner5[$i5]])
                ]]);
            }
        }
        else{
            //условия перебора, если предыдущая комбинация не подошла
            if ($i5_count > $i5){
                $i5++;
            }
            if ($i5_count == $i5){
                $i5=1;
                $i4++;
            }
            if ($i4_count == $i4){
                $i4=1;
                $i3++;
            }
            if ($i3_count == $i3){
                $i3=1;
                $i2++;
            }
            if ($i2_count == $i2){
                $i2=1;
                $i1++;
            }

            return podbor($arrAll, $i1, $i2, $i3, $i4, $i5,
                $proteins_min, $proteins_max, $fats_min, $fats_max, $carbo_min, $carbo_max, $days, $current_day);
        }
}

$res = podbor($arrAll, 0, 0, 0, 0, 0,
    $proteins_min, $proteins_max, $fats_min, $fats_max, $carbo_min, $carbo_max, $days, $current_day = 1);

?><pre style="color: #fff;">
<? print_r($res); ?>
</pre>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>