<?php
require __DIR__ . '/Array2XML.php';

class ExampleDisplay
{
    private $media_type_prefix = 'application/';
    private $media_type = 'hal';
    private $format = 'json';
    private $forwiki = false;

    public $required_headers = array('FOXYCART-API-VERSION: 1');

    function setMediaType($type)
    {
        $this->media_type = $type;
    }
    function setFormat($format)
    {
        $this->format = $format;
    }
    function setForWiki($forwiki)
    {
        $this->forwiki = $forwiki;
    }

    function getAcceptsHeaderValue()
    {
        return $this->media_type_prefix . $this->media_type . '+' . $this->format;
    }

    function getRequestContentType()
    {
        return $this->media_type_prefix . $this->format;
    }

    function getHeaders($token = '')
    {
        $headers = array_merge(
                $this->required_headers,
                array(
                        'Accept: ' . $this->getAcceptsHeaderValue(),
                        'Content-Type: ' . $this->getRequestContentType()
                )
        );
        if ($token != '') {
            $headers[] = 'Authorization: Bearer '. $token;
        }
        return $headers;
    }

    function formatRequestData($data)
    {
        if ($this->getRequestContentType() == 'application/json') {
            return json_encode($data);
        } else {
            $xmlDomObj = Array2XML::createXML('result', $data);
            return $xmlDomObj->saveXML();
        }
    }

    function displayBegin()
    {
        if ($this->forwiki) {
            print '<textarea>';
        } else {
            print '<!DOCTYPE html>
<html>
  <head>
    <title>Example Requests for the FoxyCart Hypermedia API</title>
    <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap-combined.min.css" rel="stylesheet">
    <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/js/bootstrap.min.js"></script>
  </head>
  <body>
  
    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container-fluid">
          <a class="brand">FoxyCart Hypermedia API</a>
          <div class="nav-collapse">
            <ul class="nav">
              <li><a href="http://wiki.foxycart.com/v/0.0.0/hypermedia_api">Documentation</a></li>
              <li><a href="https://api-sandbox.foxycart.com/hal-browser/hal_browser.html#/">Hal Browser</a></li>
              <li><a href="https://api-sandbox.foxycart.com/hal-browser/">Link Relationships Diagram</a></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>
  
    <br /><br /><br />
    
    <div class="container">
        <h1>Example Requests for the FoxyCart Hypermedia API</h1>

        <p class="lead">Welcome to our example of the HyperClient!</p>
        <p>Below are requests and responses against the FoxyCart Hypermedia API
        using HyperClient that were generated by loading this page in your browser. Each request also shows an example curl command line
        so you can run the command yourself. If you have any questions, please review our API documentation or visit our <a href="https://github.com/FoxyCart/HyperClient">Github page</a>.
        </p>
        <p>View as <a href="?format=xml&media_type=hal">hal+xml</a></p>
        <p>View as <a href="?format=json&media_type=hal">hal+json</a></p>
        <p>View as <a href="?format=json&media_type=vnd.siren">vnd.siren</a></p>
        <p>View <a href="?forwiki=1">for docuwiki</a></p>
            ';
        }
    }

    function displayEnd()
    {
        if ($this->forwiki) {
            print '</textarea>';
        } else {
            print '
     </div>
     </body>
     </html>
             ';
        }
    }

    function displayResult($description, $client)
    {
        $display_uri = $client->last_request['uri'];

        $curl_command = 'curl';
        $curl_command .= ' -i -X ' . $client->last_request['method'];
        $data = '';
        if (count($client->last_request['data'])) {
            $data = is_array($client->last_request['data']) ? http_build_query($client->last_request['data']) : $client->last_request['data'];
            if ($client->last_request['method'] != 'GET') {
                $curl_command .= ' -d "' . htmlentities(str_replace('"','^"',$data)) . '"';
            } else {
                $display_uri .= '?' . $data;
            }
        }
        foreach($client->last_request['headers'] as $header) {
            $curl_command .= ' -H "' . $header . '"';
        }
        $curl_command .= ' ' . $display_uri;

$wiki_display_template = '

===== {{ description }} =====
==== {{ method }}: {{ uri }} ====
<code>
DATA: {{ data }}
HEADERS: {{ request_headers }}
{{ curl_command }}
</code>
== Response Header ==
<code>
{{ headers }}
</code>
== Response Body ==
<code javascript>
{{ body }}
</code>

';

$display_template = '

<h3>{{ description }}</h3>
<h4>Request</h4>
{{ method }}: {{ uri }}<br />
DATA: {{ data }}<br />
HEADERS: {{ request_headers }}<br />
<pre>
{{ curl_command }}
</pre>
<h4>Response</h4>
<pre>
{{ headers }}
</pre>
<pre>
{{ body }}
</pre>
';

        $template = ($this->forwiki) ? $wiki_display_template : $display_template;
        $template = str_replace('{{ description }}',$description,$template);
        $template = str_replace('{{ method }}',$client->last_request['method'],$template);
        $template = str_replace('{{ uri }}',$display_uri,$template);
        $template = str_replace('{{ data }}',$client->last_request['data'],$template);
        $template = str_replace('{{ request_headers }}',print_r($client->last_request['headers'],true),$template);
        $template = str_replace('{{ curl_command }}',$curl_command,$template);
        $headers = htmlspecialchars($client->last_response['header']);
        //$headers = ($this->forwiki) ? $headers : nl2br($headers);
        $template = str_replace('{{ headers }}',$headers,$template);
        $body = ($this->forwiki) ? $client->last_response['body'] : htmlspecialchars($client->last_response['body']);
        $template = str_replace('{{ body }}',$body,$template);

        print $template;
    }

    function getToken($resp)
    {
        if ($this->media_type == 'hal') {
            return $tokens['client'] = $resp['data']->token->access_token;
        }
        if ($this->media_type == 'vnd.siren') {
            return $tokens['client'] = $resp['data']->properties->token->access_token;
        }
    }
    
}