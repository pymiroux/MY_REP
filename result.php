<?
$menu=2;
require("block/header.php");
$tblslide=getSlide();
$sql="select * from ccam where code='".sanitize_string($_GET["acte"])."'";
$link=query($sql);
$tbl=fetch($link);

/*
$sql="select * from texteanalyse where code='".$_GET["acte"]."'";
$link=query($sql);
$tbl=fetch($link);
*/

//print_r($_GET);
//appel du webservice
ini_set("soap.wsdl_cache_enabled", "0");
//$client = new SoapClient("webservice/expertise.wsdl", array('trace'=> 1, 'exceptions' => 0,'soap_version' => SOAP_1_2)); 
//$client = new SoapClient("http://recette.experteo.com/Hospi/ws/expertise.wsdl", array('trace'=> 1, 'exceptions' => 0,'soap_version' => SOAP_1_2)); 
$client = new SoapClient("webservice/expertise1.2.wsdl", array('trace'=> 1, 'exceptions' => 0)); 


$mydemande2 = new stdClass;

$EnTete = new stdClass;
//$EnTete->DateMessage='2016-04-12T09:45:07.000+02:00';
$EnTete->DateMessage=date('c');
$EnTete->NomClient='ITELIS';
$EnTete->NomUtilisateur='ITE_AMC_HOSPI';
$EnTete->MotDePasse='AMC_HOSPI';
$EnTete->Origine='6';

$CodePostal = new stdClass;
$tab=explode("-",$_GET["city"]);
$CodePostal->CodePostal=trim($tab[count($tab)-1]);

$mydemande2->EnTete=$EnTete;
$mydemande2->VersionWsdl='1.2';
$mydemande2->CodeAMC=$_SESSION["infos"]["codeamc"];
$mydemande2->Etablissement=$CodePostal;

$Acte= new stdClass;
$Acte->CotationCCAM=strtoupper($_GET["acte"]);
$Acte->Anesthesie=0;
$Acte->MontantDepassement=$_GET["depassement"]*100;

$mydemande2->Acte=$Acte;

try {
    $result = $client->Expertise($mydemande2); 
    //print_r($result);
    $DepassementRaisonnable=$result->DepassementRaisonnable/100;
    $Positionnement=$result->Acte->Positionnement;
    $positionnement=$result->Acte->Positionnement;
    $frequence=$result->Acte->Marche->FrequenceDepassements;
    
    if($DepassementRaisonnable==0){
      $sql="select * from texteanalyse t 
        left join montant_analyse ma on t.texteanalyse_id=ma.texteanalyse_id
        where ma.texteanalyse_id is null";
    }else{
      $sql="select * from texteanalyse t 
        inner join montant_analyse ma on t.texteanalyse_id=ma.texteanalyse_id
        inner join montant m on m.montant_id=ma.montant_id where montant='".$positionnement."' and frequence='".$frequence."'";
    }
    //print $sql;
    $link=query($sql);
    $tbltext=fetch($link);
    //print "ici";
    $pourcentage=($_GET["depassement"]*100)/$tbl["montant"];
  } catch (Exception $e) {
      echo($client->__getLastResponse());
      echo PHP_EOL;
      echo($client->__getLastRequest());
  }
  
  
?>
    <main>
        <div class="top-block">
            <div class="top-img" style='background-image: url("<?=$tblslide[1]?>")'>
                <div class="container">
                    <div class="title">
                        <img src="pictures/analyser2.svg" width="42" alt="">
                    </div>
                    <h1>Analyser votre devis hospitalier</h1>
                            
                </div>
            </div>
        </div>
        <div class="content analyze">
            <div class="container">
                <form action="" class="step-2">
                    <div class="form-block">
                        <div class="hidden-info-wrap open">
                            <h3 class="sub-title hidden-info-title"><span>Acte</span></h3>
                            <h3><?=$tbl["description"]?></h3>
                            <div class="hidden-info">
                                <div class="box">
                                    <p>Base de remboursement de la Sécurité Sociale : <span><?=formatval2($tbl["montant"])?>€</span></p>
                                    <p>(tarif officiel de l’acte)</p>
                                </div>
                                <div class="box">
                                    <p>Dépassement d’honoraires :  <span><?=$_GET["depassement"]?>€</span></p>
                                    <p>(supplément facultatif d’honoraire) :<span> soit + <?=round($pourcentage)?>%</span></p>
                                </div>
                                <?if($DepassementRaisonnable>0){?>
                                <div class="box">
                                    <p>Comparatif du montant du supplément d’honoraire par rapport à ceux couramment pratiqués dans la région :</p>
                                </div>
                                <div class="scale" data-start="0" data-finish="<?=$_GET["depassement"]+$DepassementRaisonnable?>" data-point="<?=$_GET["depassement"]?>">
                                    <div class="part start-bar"></div>
                                    <div class="part medium-bar"></div>
                                    <div class="part final-bar"></div>
                                    <div class="start"><span>0,00 €</span></div>
                                    <div class="med"><span><?=formatval2($DepassementRaisonnable)?>€</span><span class="bottom" style="width:90px">Médiane&nbsp;<img src="i/faq-icon.png" alt="question-mark" title="Il y a autant de données plus chères que de données moins chères que la médiane dans notre échantillon" class="tooltip icon-img"></span></div>
                                    <span class="circle"><?=round($_GET["depassement"])?>€ <!-- <span class="bottom">votre dépassement</span> --></span>
                                </div>
                                <?}?>
                            </div>
                        </div>
                        <p>
                        <?=$tbltext["texte"]?>
                        </p>
                        
                        <a href="analyse-2.php?city=<?=$_GET["city"]?>&hopital=<?=$_GET["hopital"]?>&acte=<?=$_GET["acte"]?>" onClick="ga('send', {'hitType': 'event', 'eventCategory': 'Analyse-Devis', 'eventAction': 'Click', 'eventLabel': 'Analyse-Lien-2', 'eventValue': 1});" class="res-controls edit-info">Evaluer d'autres honoraires</a>
                        <!--
                        <a href="" class="res-controls pdf">Générer un PDF</a>
                        -->
                    </div>
                    <!--
                    <div class="form-block advice">
                        
                        <div class="hidden-info-wrap">
                            <h3 class="sub-title hidden-info-title"><span>Connaître les critères utilisés pour analyser votre devis </span></h3>
                            <div class="hidden-info">
                                <p>Les dépassements d’honoraires (compléments d’honoraires demandés par un professionnel de santé pour la réalisation d’une prestation de santé) ne sont pas pris en charge par l’Assurance Maladie. Ils sont éventuellement pris en charge par votre complémentaire santé à hauteur de votre garantie. </p>
                                <p>Les dépassements d’honoraires peuvent porter sur l’acte chirurgical comme sur l’acte d’anesthésie. Vous pouvez demander un devis à  plusieurs médecins spécialistes avant votre hospitalisation, pour  comparer leurs tarifs. Puis adressez le (ou les) devis à votre complémentaire santé afin de connaître le montant de votre remboursement et de votre reste à charge.</p>
                                <p>L’Assurance Maladie prend en charge 80% : </p>
                                <ul>
                                    <li>des frais de séjour, </li>
                                    <li>des tarifs de base des actes d’anesthésie et chirurgicaux et </li>
                                    <li>des autres actes réalisés lors du séjour (prise de sang, kinésithérapeute, etc. )</li>
                                </ul>
                            </div>
                        </div>
                        <div class="hidden-info-wrap">
                            <h3 class="sub-title hidden-info-title"><span>En parler avec votre médecin </span></h3>
                            <div class="hidden-info">
                                <p>Les dépassements d’honoraires (compléments d’honoraires demandés par un professionnel de santé pour la réalisation d’une prestation de santé) ne sont pas pris en charge par l’Assurance Maladie. Ils sont éventuellement pris en charge par votre complémentaire santé à hauteur de votre garantie. </p>
                                <p>Les dépassements d’honoraires peuvent porter sur l’acte chirurgical comme sur l’acte d’anesthésie. Vous pouvez demander un devis à  plusieurs médecins spécialistes avant votre hospitalisation, pour  comparer leurs tarifs. Puis adressez le (ou les) devis à votre complémentaire santé afin de connaître le montant de votre remboursement et de votre reste à charge.</p>
                                <p>L’Assurance Maladie prend en charge 80% : </p>
                                <ul>
                                    <li>des frais de séjour, </li>
                                    <li>des tarifs de base des actes d’anesthésie et chirurgicaux et </li>
                                    <li>des autres actes réalisés lors du séjour (prise de sang, kinésithérapeute, etc. )</li>
                                </ul>
                            </div>
                        </div>
                        <div class="hidden-info-wrap">
                            <h3 class="sub-title hidden-info-title"><span>Consulter un médecin ayant adhéré au CAS / OPTAM </span></h3>
                            <div class="hidden-info">
                                <p>Les dépassements d’honoraires (compléments d’honoraires demandés par un professionnel de santé pour la réalisation d’une prestation de santé) ne sont pas pris en charge par l’Assurance Maladie. Ils sont éventuellement pris en charge par votre complémentaire santé à hauteur de votre garantie. </p>
                                <p>Les dépassements d’honoraires peuvent porter sur l’acte chirurgical comme sur l’acte d’anesthésie. Vous pouvez demander un devis à  plusieurs médecins spécialistes avant votre hospitalisation, pour  comparer leurs tarifs. Puis adressez le (ou les) devis à votre complémentaire santé afin de connaître le montant de votre remboursement et de votre reste à charge.</p>
                                <p>L’Assurance Maladie prend en charge 80% : </p>
                                <ul>
                                    <li>des frais de séjour, </li>
                                    <li>des tarifs de base des actes d’anesthésie et chirurgicaux et </li>
                                    <li>des autres actes réalisés lors du séjour (prise de sang, kinésithérapeute, etc. )</li>
                                </ul>
                            </div>
                        </div>
                    </div>    -->
                    <!--
                    <span data-target="ip-1" class="inner-popup-open inner-popup-open-ver-2">Connaitre les critères utilisés pour analyser votre devis</span>
                    
                    <button type="submit" class="next btn">Lancer l’analyse</button>
                    -->
                    <div class="inner-popup ip-1">
                        <p>Les dépassements d’honoraires (compléments d’honoraires demandés par un professionnel de santé pour la réalisation d’une prestation de santé) ne sont pas pris en charge par l’Assurance Maladie. Ils sont éventuellement pris en charge par votre complémentaire santé à hauteur de votre garantie. </p>
                        <p>Les dépassements d’honoraires peuvent porter sur l’acte chirurgical comme sur l’acte d’anesthésie. Vous pouvez demander un devis à  plusieurs médecins spécialistes avant votre hospitalisation, pour  comparer leurs tarifs. Puis adressez le (ou les) devis à votre complémentaire santé afin de connaître le montant de votre remboursement et de votre reste à charge.</p>
                        <span class="close">×</span>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <div id="indent"></div>
</div>

<div id="faq" class="outer-popup mfp-hide">
    <div class="popup-container">
        <?=getFaq()?></div>
</div>

<?
require("block/footer.php");
?>