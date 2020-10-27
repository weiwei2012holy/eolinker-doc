<?php
/**
 * Desc: api doc 解析工具
 * Author: 余伟<weiwei2012holy@hotmail.com>
 * Date: 2019-06-10,22:54
 */

namespace Weiwei2012holy\EolinkerDoc\Models;


use Illuminate\Support\Str;

class ApiDocParseTool
{
    const API = '@api';

    /**
     * 解析获取代码注释块
     *
     * @param string $comment
     *
     * @return string|string[]|null
     */
    public function parseDoc(string $comment)
    {
        $comments = [];
        if (empty($comment)) {
            return $comments;
        }
        // 获取注释
        if (preg_match('#^/\*\*(.*)\*/#s', $comment, $matches) === false) {
            return $comments;
        }
        $matches = trim($matches[1]);
        // 按行分割注释
        if (preg_match_all('#^\s*\*(.*)#m', $matches, $lines) === false) {
            return $comments;
        }
        $comments = $lines[1];
        $data = collect();
        $keyReg = '/\@(api\w*)\s(.*)/';
        $apiInfo = [];
        foreach ($comments as $k => $c) {
            $c = trim($c);
            //换行数据 归到一行
            if (!Str::contains($c, self::API) && !$data->isEmpty()) {
                $c = $data->pop() . trim($c);
            }
            $data->push($c);
        }
        foreach ($data as $item) {
            preg_match($keyReg, $item, $matchesKey);
            $temp = ['key' => $matchesKey[1], 'value' => trim($matchesKey[2])];
            $fn = 'parse' . Str::studly($temp['key']);
            if (method_exists($this, $fn)) {
                if (!$temp['value']) {
                    continue;
                }
                $temp['parse'] = $this->$fn($temp['value'], $apiInfo);
            }
        }
        return $apiInfo;
    }

    /**
     * 解析api定义
     *
     * @param string $content
     * @param array  $outPut
     */
    public function parseApi(string $content, &$outPut)
    {
        $reg = '/^(?:(?:\{(.+?)\})?\s*)?(.+?)(?:\s+(.+?))?$/';
        preg_match_all($reg, $content, $matches);
        $data = [
            'method' => $matches[1][0],
            'api' => $matches[2][0],
            'title' => $matches[3][0] ?: ''
        ];
        $outPut = array_merge($outPut, $data);
    }

    /**
     * 解析参数
     *
     * @param string $content
     * @param array  $outPut
     */
    public function parseApiParam(string $content, &$outPut)
    {
        $outPut['params'][] = $this->parseParam($content);
    }

    /**
     * 返回值解析
     *
     * @param string $content
     * @param array  $outPut
     */
    public function parseApiSuccess(string $content, &$outPut)
    {
        $outPut['success'][] = $this->parseParam($content);
    }

    /**
     * @param string $content
     *
     * @return array
     */
    private function parseParam(string $content)
    {
        //千万别动这个正则
        $reg = '/^\s*(?:\(\s*(.+?)\s*\)\s*)?\s*(?:\{\s*([a-zA-Z0-9()#:\.\/\\\[\]_-]+)\s*(?:\{\s*(.+?)\s*\}\s*)?\s*(?:=\s*(.+?)(?=\s*\}\s*))?\s*\}\s*)?(\[?\s*([a-zA-Z0-9\:\.\/\\_-]+(?:\[[a-zA-Z0-9\.\/\\_-]*\])?)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(.*?)(?:\s|\]|$)))?\s*\]?\s*)(.*)?$|@/';
        $matches = [];
        preg_match_all($reg, $content, $matches);
        $data = [
            'group' => $matches[1][0],
            'type' => $matches[2][0],
            'size' => $matches[3][0],
            'allowed_values' => $matches[4][0],
            'optional' => ($matches[5][0][0] == '[') ? true : false,
            'field' => $matches[6][0],
            'default_value' => $matches[7][0] ?: ($matches[8][0] ?: $matches[9][0]),
            'description' => $matches[10][0]
        ];
        //解析绑定的model和参数 格式 FuleNameOfModel@field1,field2
        $reg = '/\{(?:(.*)@(.*)|(.*))\}/is';
        preg_match_all($reg, $data['default_value'], $model);
        if ($data['model'] = $model[1][0] ?: $model[3][0]) {
            $data['default_value'] = '';
        }
        if (trim($model[2][0])) {
            $data['model_field'] = explode(',', trim($model[2][0]));
        } else {
            $data['model_field'] = [];
        }
        return $data;
    }


    /**
     * @param string $content
     * @param        $outPut
     */
    public function parseApiName(string $content, &$outPut)
    {
        $outPut['api_name'] = trim($content);
    }

    /**
     * @param string $content
     * @param array  $outPut
     */
    public function parseApiDescription(string $content, &$outPut)
    {
        $outPut['api_description'] = trim($content);
    }


    /**
     * @param string $content
     * @param        $outPut
     */
    public function parseApiParamExample(string $content, &$outPut)
    {
        $outPut['api_param_example'] = $this->parseExample($content);
    }

    /**
     * @param string $content
     * @param        $outPut
     */
    public function parseApiSuccessExample(string $content, &$outPut)
    {
        $outPut['api_success_example'] = $this->parseExample($content);
    }

    /**
     * @param string $content
     * @param        $outPut
     */
    public function parseApiErrorExample(string $content, &$outPut)
    {
        $outPut['api_error_example'] = $this->parseExample($content);
    }

    /**
     * @param string $content
     *
     * @return array
     */
    public function parseExample(string $content)
    {
        $firstReg = '/(@\w*)?(?:(?:\s*\{\s*([a-zA-Z0-9\.\/\\\[\]_-]+)\s*\}\s*)?\s*(.*)?)?/';
        $matches = [];
        preg_match_all($firstReg, $content, $matches);

        $data = [
            'content' => $matches[3][0],
            'type' => $matches[2][0] ?: 'json',
        ];

        if (strtolower($data['type']) == 'json') {
            $reg = '/(\{.*\})/is';
            preg_match($reg, $data['content'], $fm);
            $data['content_json'] = $fm[1];
        }
        return $data;
    }

    /**
     * @param string $content
     * @param        $outPut
     */
    public function parseApiStatus(string $content, &$outPut)
    {
        $outPut['status'] = strtolower(trim($content));
    }

}
