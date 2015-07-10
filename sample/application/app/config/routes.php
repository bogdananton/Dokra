<?php

return array(
    array(
        "url" => "soap-rpc/{api_endpoint}/v{api_version}",
        "method" => "GET",
        "use" => "SoapController@get_rpc"
    ),
    array(
        "url" => "soap-rpc/{api_endpoint}/v{api_version}",
        "method" => "POST",
        "use" => "SoapController@post_rpc"
    ),
    // array(
    //     "url" => "soap-literal/{api_endpoint}/v{api_version}",
    //     "method" => "GET",
    //     "use" => "SoapController@get_literal"
    // ),
    // array(
    //     "url" => "soap-literal/{api_endpoint}/v{api_version}",
    //     "method" => "POST",
    //     "use" => "SoapController@post_literal"
    // ),
    array(
        "url" => "json-rpc/{api_endpoint}/v{api_version}",
        "method" => "POST",
        "use" => "JsonController@post_index"
    )
);