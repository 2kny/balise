<?php

  function frankiz_do_auth() {
    $query_array["response"] = "coucou";
    redirect_to_path(path("login", "home", "", "", $query_array));
  }

  function frankiz_get_response() {
    // TODO : understand why WEBMASTER_MAIL is inserted instead of its value
    return array("hruid" => "qmlsdfkjqmsdjq", "firstname" => "Georges", "lastname" => "Blanquette", "email" => WEBMASTER_MAIL);
  }
