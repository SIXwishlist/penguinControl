<?php

namespace App\Http\Controllers;

use App\Alert;
use App\Models\Log;
use App\Models\SystemTask;
use App\Models\Vhost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class VHostController extends Controller
{
	public function index ()
	{
		$user = Auth::user ();
		$userInfo = $user->userInfo;
		$vhosts = Vhost::accessible ()->get ();
		
		$apacheReloadInterval = SystemTask::where ('type', SystemTask::TYPE_APACHE_RELOAD)
			->where
			(
				function ($query)
				{
					$query->where ('end', '>', time ())
						->orWhereNull ('end');
				}
			)->min ('interval');
		$apacheReloadInterval = SystemTask::friendlyInterval ($apacheReloadInterval);
		
		return view ('website.vhost.index', compact ('user', 'userInfo', 'vhosts', 'apacheReloadInterval'));
	}
	
	public function create ()
	{
		$user = Auth::user ();
		$userInfo = $user->userInfo;
		
		if (! Vhost::allowNew ($user))
			return Redirect::to ('/website/vhost')->with ('alerts', array (new Alert ('You are only allowed to create ' . Vhost::getLimit ($user) . ' vHosts.', Alert::TYPE_ALERT)));
		
		return view ('website.vhost.create', compact ('user', 'userInfo'));
	}

	public function store ()
	{
		$user = Auth::user ();
		
		if (! Vhost::allowNew ($user))
			return Redirect::to ('/website/vhost/create')->withInput ()->with ('alerts', array (new Alert ('You are only allowed to create ' . Vhost::getLimit ($user) . ' vHosts.', Alert::TYPE_ALERT)));
		
		$servername = @strtolower (Input::get ('servername'));
		$serveralias = @strtolower (Input::get ('serveralias'));
		$docroot = @trailing_slash (Input::get ('docroot'));
		
		$validator = Validator::make
		(
			array
			(
				'Host' => $servername,
				//'Beheerder' => Input::get ('serveradmin'),
				'Aliases' => $serveralias,
				'Document root' => $docroot,
				'Protocol' => Input::get ('ssl'),
				'CGI' => Input::get ('cgi')
			),
			array
			(
				'Host' => array ('required', 'unique:vhost,servername', 'unique:vhost,serveralias', 'regex:/^[a-zA-Z0-9\.\_\-]+\.[a-zA-Z0-9\.\_\-]+$/'),
				//'Beheerder' => array ('required', 'email'),
				'Aliases' => array ('nullable', 'different:Host', 'unique:vhost,servername', 'unique:vhost,serveralias', 'regex:/^[a-zA-Z0-9\.\_\-]+\.[a-zA-Z0-9\.\_\-]+(\s[a-zA-Z0-9\.\_\-]+\.[a-zA-Z0-9\.\_\-]+)*$/'),
				'Document root' => array ('regex:/^([a-zA-Z0-9\_\.\-\/]+)?$/', 'not_in:www/public/'),
				'Protocol' => array ('required', 'in:0,1,2'),
				'CGI' => array ('required', 'in:0,1')
			)
		);
		
		if ($validator->fails ())
			return Redirect::to ('/website/vhost/create')->withInput ()->withErrors ($validator);
		
		$serveralias = 'www.' . $servername . (empty ($serveralias) ? '' : ' ' . $serveralias);
		
		$vhost = new Vhost ();
		$vhost->uid = $user->uid;
		$vhost->docroot = $user->homedir . '/' . $docroot;
		$vhost->servername = $servername;
		$vhost->serveralias = $serveralias;
		$vhost->serveradmin = $user->userInfo->username . '@' . $servername;
		$vhost->ssl = (int) Input::get ('ssl');
		$vhost->cgi = (bool) Input::get ('cgi');
		
		$vhost->save ();
		
		Log::log ('vHost created', $user->id, $vhost);
		
		$task = new SystemTask ();
		$task->type = SystemTask::TYPE_CREATE_VHOST_DOCROOT;
		$task->data = json_encode (['vhostId' => $vhost->id]);
		$task->save ();
		
		if ($vhost->ssl > 0)
		{
			$task = new SystemTask ();
			$task->type = SystemTask::TYPE_VHOST_OBTAIN_CERTIFICATE;
			$task->data = json_encode (['vhostId' => $vhost->id, 'redirect' => $vhost->ssl == 2]);
			$task->save ();
		}
		
		return Redirect::to ('/website/vhost')->with ('alerts', array (new Alert ('vHost created', Alert::TYPE_SUCCESS)));
	}
	
	public function edit ($vhost)
	{
		$user = Auth::user ();
		$insideHomedir = substr ($vhost->docroot, 0, strlen ($vhost->user->homedir)) == $vhost->user->homedir;
		
		return view ('website.vhost.edit', compact ('user', 'vhost', 'insideHomedir'));
	}
	
	public function update ($vhost)
	{
		$user = Auth::user ();
		$insideHomedir = (Input::get ('outsideHomedir') !== 'true');
		$docroot = @trailing_slash (Input::get ('docroot'));
		$serveralias = @strtolower (Input::get ('serveralias'));
		
		$validator = Validator::make
		(
			array
			(
				'Document root' => $docroot,
				'Aliases' => $serveralias,
				'Protocol' => Input::get ('ssl'),
				'CGI' => Input::get ('cgi')
			),
			array
			(
				'Document root' => array ($insideHomedir ? 'regex:/^([a-zA-Z0-9\_\.\-\/]+)?$/' : 'optional'),
				'Aliases' => array ('nullable', 'unique:vhost,servername', 'unique:vhost,serveralias,' . $vhost->id, 'regex:/^[a-zA-Z0-9\.\_\-]+\.[a-zA-Z0-9\.\_\-]+(\s[a-zA-Z0-9\.\_\-]+\.[a-zA-Z0-9\.\_\-]+)*$/'),
				'Protocol' => array ('required', 'in:0,1,2'),
				'CGI' => array ('required', 'in:0,1')
			)
		);
		
		if ($validator->fails ())
			return Redirect::to ('website/vhost/' . $vhost->id . '/edit')
				->withInput ()
				->withErrors ($validator);
		
		$oldDocroot = $vhost->docroot;
		if ($insideHomedir)
			$vhost->docroot = $vhost->user->homedir . '/' . $docroot;
		$vhost->serveralias = $serveralias;
		$vhost->ssl = (int) Input::get ('ssl');
		$vhost->cgi = (bool) Input::get ('cgi');
		
		$vhost->save ();
		
		Log::log ('vHost modified', NULL, $vhost);
		
		if ($vhost->ssl > 0)
		{
			$task = new SystemTask ();
			$task->type = SystemTask::TYPE_VHOST_OBTAIN_CERTIFICATE;
			$task->data = json_encode (['vhostId' => $vhost->id, 'redirect' => $vhost->ssl == 2]);
			$task->save ();
		}
		
		$task = new SystemTask ();
		if ($insideHomedir && $oldDocroot != $vhost->docroot)
		{
			$task->type = SystemTask::TYPE_CREATE_VHOST_DOCROOT;
			$task->data = json_encode (['vhostId' => $vhost->id]);
		}
		else
		{
			$task->type = SystemTask::TYPE_APACHE_RELOAD;
		}
		$task->save ();
		
		return Redirect::to ('/website/vhost')->with ('alerts', array (new Alert ('vHost changes saved', Alert::TYPE_SUCCESS)));
	}
	
	public function remove ($vhost)
	{
		$user = Auth::user ();
		
		$vhost->delete ();
		
		Log::log ('vHost removed', $user->id, $vhost);
		
		$task = new SystemTask ();
		$task->type = SystemTask::TYPE_APACHE_RELOAD;
		$task->save ();
		
		return Redirect::to ('/website/vhost')->with ('alerts', array (new Alert ('vHost removed', Alert::TYPE_SUCCESS)));
	}

}
