<?php

  function check_unique_clean_name() {
    $binets = select_binets(array("clean_name" => clean_string($_POST["name"])));
    if (!is_empty($binets)) {
      $_SESSION["binet"]["errors"][] = "name";
      $_SESSION["error"][] = "Ce nom de binet est déjà utilisé par le binet ".pretty_binet($binets[0]["id"]).". Veuillez en choisir un autre.";
      return false;
    }
    return true;
  }

  function check_is_deactivated() {
    $current_term = current_term($GLOBALS["binet"]["id"]);
    header_if(!is_empty($current_term), 403);
  }

  function check_is_activated() {
    $current_term = current_term($GLOBALS["binet"]["id"]);
    header_if(is_empty($current_term), 403);
  }

  before_action("check_csrf_post", array("update", "create", "reactivate"));
  before_action("check_csrf_get", array("delete", "set_subsidy_provider", "deactivate", "power_transfer"));
  before_action(
    "check_entry",
    array("edit", "update", "set_subsidy_provider", "show", "change_term", "reactivate", "deactivate", "power_transfer"),
    array("model_name" => "binet")
  );
  before_action("check_is_activated", array("power_transfer", "deactivate"));
  before_action("check_is_deactivated", array("reactivate"));
  before_action("current_kessier", array("new", "create", "power_transfer", "change_term", "deactivate", "reactivate", "set_subsidy_provider", "admin"));
  before_action("check_editing_rights", array("edit", "update"));
  before_action("check_form_input", array("create"), array(
    "model_name" => "binet",
    "str_fields" => array(array("name", 30)),
    "other_fields" => array(array("name", "check_unique_clean_name")),
    "int_fields" => array(array("term", MAX_TERM)),
    "redirect_to" => path("new", "binet", "")
  ));
  before_action("check_form_input", array("update"), array(
    "model_name" => "binet",
    "str_fields" => array(array("description", 10000), array("subsidy_steps", 50000)),
    "redirect_to" => path("edit", "binet", $_GET["action"] == "update" ? $binet["id"] : ""),
    "optional" => array("description", "subsidy_steps")
  ));
  before_action("check_form_input", array("reactivate"), array(
    "model_name" => "binet",
    "str_fields" => array(array("term", MAX_TERM)),
    "redirect_to" => path("change_term", "binet", $_GET["action"] == "reactivate" ? $binet["id"] : "")
  ));
  before_action("generate_csrf_token", array("new", "edit", "change_term", "show"));

  $binet_form_fields = array("name", "term");
  $description_form_fields = array("name", "description", "subsidy_steps");
  $term_form_fields = array("term");

  switch ($_GET["action"]) {

  case "index":
    $binets = select_binets(array("current_term" => array("IS", "NOT NULL")));
    break;

  case "new":
    $binet = initialise_for_form_from_session($binet_form_fields, "binet");
    break;

  case "create":
    $binet["id"] = create_binet($_POST["name"], $_POST["term"]);
    $_SESSION["notice"][] = "Le binet ".pretty_binet($binet["id"])." a été créé avec succès.";
    redirect_to_action("show");
    break;

  case "edit":
    function binet_to_form_fields($binet) {
      return $binet;
    }
    $binet = set_editable_entry_for_form("binet", $binet, $description_form_fields);
    break;

  case "update":
    update_binet($binet["id"], $_POST);
    $_SESSION["notice"][] = "Le binet ".pretty_binet($binet["id"])." a été mis à jour avec succès.";
    redirect_to_action("show");
    break;

  case "set_subsidy_provider":
    set_subsidy_provider($binet["id"]);
    $_SESSION["notice"][] = "Le binet ".pretty_binet($binet["id"])." est devenu un binet subventionneur.";
    redirect_to_action("show");
    break;

  case "show":
    $binet = select_binet($binet["id"], array("id", "name", "description", "current_term", "subsidy_provider", "subsidy_steps"));
    $binet = array_merge(select_term_binet($binet["id"]."/".$binet["current_term"], array("subsidized_amount_used", "subsidized_amount_granted", "subsidized_amount_requested", "real_spending", "real_income", "real_balance", "expected_spending", "expected_income", "expected_balance", "state")), $binet);
    foreach (select_waves(array("binet" => $binet["id"]), "submission_date") as $wave) {
      $waves[] = select_wave($wave["id"], array("id", "binet", "term", "submission_date", "expiry_date", "published"));
    }
    break;

  case "change_term":
    function binet_to_form_fields($binet) {
      $binet["term"] = $binet["current_term"];
      return $binet;
    }
    $binet = set_editable_entry_for_form("binet", $binet, $term_form_fields);
    break;

  case "power_transfer":
    change_term_binet($binet["id"], current_term($binet["id"]) + 1);
    $_SESSION["notice"][] = "La passation du binet ".pretty_binet($binet["id"])." s'est déroulée avec succès !";
    redirect_to_action("show");
    break;

  case "reactivate":
    change_term_binet($binet["id"], $_POST["term"]);
    $_SESSION["notice"][] = "Le binet ".pretty_binet($binet["id"])." existe à nouveau avec la promotion ".$_POST["term"]." !";
    redirect_to_action("show");
    break;

  case "deactivate":
    deactivate_binet($binet["id"]);
    $_SESSION["notice"][] = "Le binet ".pretty_binet($binet["id"])." a été désactivé avec succès.";
    redirect_to_action("show");
    break;

  default:
    header_if(true, 403);
    exit;
  }
