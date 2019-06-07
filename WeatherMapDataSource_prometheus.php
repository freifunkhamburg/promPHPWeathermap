<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a static value

// TARGET static:10M
// TARGET static:2M:256K

class WeatherMapDataSource_prometheus extends WeatherMapDataSource {

	function Recognise($targetstring)
	{
		if( preg_match("/^prometheus:.*$/", $targetstring) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	function ReadData($targetstring, &$map, &$item)
	{
		$parts = explode(':', $targetstring);
		$router = $parts[1];
		$device = $parts[2];

		$query_rx = "irate(node_network_receive_bytes{instance='$router:9100',device='$device'}[5m])";
		$query_tx = "irate(node_network_transmit_bytes{instance='$router:9100',device='$device'}[5m])";

		$rx_rate = $this->prometheus_query($query_rx) * 8;
		$tx_rate = $this->prometheus_query($query_tx) * 8;

		return ( array($rx_rate, $tx_rate, 0) );
	}

	function prometheus_query($query)
	{
		$time = time();
		$query = urlencode($query);
		$url = "http://localhost:9090/api/v1/query?time=$time&query=$query";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$content = curl_exec($ch);
		$data = json_decode($content, true);

		if ($data['status'] != "success")
		{
			return -1;
		}

		if ($data['data']['resultType'] != "vector")
		{
			return -1;
		}

		return $data['data']['result'][0]['value'][1];
	}
}

// vim:ts=4:sw=4:
?>
