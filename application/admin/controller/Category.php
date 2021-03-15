<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use fast\Tree;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Category extends Backend
{

    /**
     * ScoreCategory模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->request->filter(['strip_tags']);
        $this->model = model('Category');
        $cate = $this->model->getLeescoreCategory();

        $tree = Tree::instance()->init($cate, 'category_id');
        $this->categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $categorydata = [];
        foreach ($this->categorylist as $k => $v) {
            $categorydata[$v['id']] = $v;
        }

        //dump($cate[0]->get_cate_name->name);
        $this->assign('options', $categorydata);
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {
            $search = $this->request->request("search");
            $type = $this->request->request("type");
            //构造父类select列表选项数据
            $list = [];

            foreach ($this->categorylist as $k => $v) {
                if ($search) {
                    if ($v['type'] == $type && stripos($v['name'], $search) !== false || stripos($v['nickname'], $search) !== false) {
                        if ($type == "all" || $type == null) {
                            $list = $this->categorylist;
                        } else {
                            $list[] = $v;
                        }
                    }
                } else {
                    if ($type == "all" || $type == null) {
                        $list = $this->categorylist;
                    } else {
                        if ($v['type'] == $type) {
                            $list[] = $v;
                        }
                    }

                }

            }
            $total = count($list);
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($params['category_id'] != 0) {
                    while (1 == 1) {
                        $rs = Db::name('category')->where('id', $params['category_id'])->find();
                        if ($rs['category_id'] == 0) {
                            $params['topid'] = $rs['id'];
                            break;
                        }
                    }
                } else {
                    $params['topid'] = 0;
                }

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                        $this->model->validate($validate);
                    }

                    $result = $this->model->allowField(true)->save($params);

                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                }
            }
            //$this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ((int)$params['category_id'] != 0) {
                    while (1 == 1) {
                        $rs = $this->model->where('id', $params['category_id'])->find();
                        if ((int)$rs['category_id'] == 0) {
                            $params['topid'] = $rs['id'];
                            break;
                        }
                    }
                } else {
                    $params['topid'] = 0;
                }

                try {

                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


    /**
     * Selectpage搜索
     *
     * @internal
     */
    public function selectpage()
    {
        return parent::selectpage();
    }

}
