<?php
// +----------------------------------------------------------------------
// | oursphp [ simple and fast ]
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: midoks <627293072@qq.com>
// +----------------------------------------------------------------------


namespace  app\controller;


class Encrypt extends Base {

	public function __construct($request, $response){
		$response->title = '加密相关';
		parent::__construct($request, $response);
	}
    	
    //展示
	public function index($request, $response) {
		$response->stitle = 'authcode';
		return $this->renderLayout();
    }

    

	
}