<?php

$src = $argv[1];
$dst = $argv[2];
$codec = $argv[3];
$preset = $argv[4];
$resolution = !empty($argv[5]) ? $argv[5] : 720;
$crf = !empty($argv[6]) ? $argv[6] : ($codec == 'h265' ? 20 : 17);
$keyframes = '';
$timecodes = '';

$dst = preg_replace('/\\.mkv$/i', '-'.$codec.$preset.preg_replace('/(([0-9]+):)?([0-9]+)$/', '\\3', $resolution).'crf'.$crf.'.mkv', $dst);

if(preg_match('/^[0-9]+/i', $resolution, $m))
{
	if($resolution == '480' || $resolution == '576')
	{
		$resolution = '720:'.$resolution;
	}
	else
	{
		$resolution = ($resolution*4/3).':'.$resolution;
	}
}

if(preg_match('/(.+title_t[0-9]+-)/i', $src, $m))
{
	$fn = $m[1].'keyframes.txt';

	if(file_exists($fn))
	{
		$keyframes = [];	
	
		foreach(explode("\n", file_get_contents($fn)) as $row)
		{
			$row = trim($row);
			if(empty($row)) continue;
			$keyframes[] = $row;
		}

		$keyframes = implode(',', $keyframes);
	}
}

if(preg_match('/(.+title_t[0-9]+-)/i', $src, $m))
{
	$fn = $m[1].'timecodes.txt';

	if(file_exists($fn)) $timecodes = $fn;
}

$cmd = [];

$cmd[] = 'ffmpeg -hide_banner';
if($res >= 720) $cmd[] = '-colorspace bt709';
$cmd[] = '-i "'.$src.'"';
$cmd[] = '-pix_fmt yuv420p';
$cmd[] = '-map 0:v';
if($codec == 'h264') $cmd[] = '-c:v libx264 -profile high -level 4.1';
else if($codec == 'h265') $cmd[] = '-c:v libx265';
$cmd[] = '-preset '.$preset.' -crf '.$crf;
$cmd[] = '-vf "scale='.$resolution.':flags=lanczos"';
$cmd[] = '-tune grain';
$cmd[] = '-aspect 4:3';
$cmd[] = '-movflags +faststart';
if(!empty($keyframes)) $cmd[] = '-force_key_frames '.$keyframes;
$cmd[] = '"'.$dst.'"';

$cmd = implode(' ', $cmd);

echo $cmd.PHP_EOL;
	
$ret = 0;
passthru($cmd, $ret);
if(!empty($ret)) die($ret);

if(!empty($timecodes))
{

$dstvfr = preg_replace('/\\.mkv$/i', '-vfr.mkv', $dst);

$cmd = <<<EOT
E:\\tmp\\media\\util\\mkvtoolnix\\mkvmerge.exe ^
--output "$dstvfr" ^
--language 0:und ^
--default-track 0:yes ^
--timestamps "0:$timecodes" ^
--fix-bitstream-timing-information 0:1 ^
"$dst"
EOT;

$cmd = preg_replace('/[\r\n]+/', ' ', $cmd);

echo $cmd.PHP_EOL;
	
$ret = 0;
passthru($cmd, $ret);
if(!empty($ret)) die($ret);

}

?>