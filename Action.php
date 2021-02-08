<?php 
/**
 * 百度站长工具 链接提交
 * 发布、更新文章后，自动提交百度链接更新
 * 详情请查看 http://dwz.cn/265Rcs
 * 
 * @package BaiduSubmit 
 * @author Arkylin
 * @version 0.0.1
 * @link http://
 */
class BaiduSubmit_Action extends Typecho_Widget implements Widget_Interface_Do {
    public function action(){
        $urls = array('https://www.xyz.blue');
        $db = Typecho_Db::get();
		$options = Typecho_Widget::widget('Widget_Options');
        $site = Helper::options()->plugin("BaiduSubmit")->site;
        $token = Helper::options()->plugin("BaiduSubmit")->token;

		$pages = $db->fetchAll($db->select()->from('table.contents')
		->where('table.contents.status = ?', 'publish')
		->where('table.contents.created < ?', $options->gmtTime)
		->where('table.contents.type = ?', 'page')
		->order('table.contents.created', Typecho_Db::SORT_DESC));

		$articles = $db->fetchAll($db->select()->from('table.contents')
		->where('table.contents.status = ?', 'publish')
		->where('table.contents.created < ?', $options->gmtTime)
		->where('table.contents.type = ?', 'post')
		->order('table.contents.created', Typecho_Db::SORT_DESC));

		foreach($pages AS $page) {
			$type = $page['type'];
			$routeExists = (NULL != Typecho_Router::get($type));
			$page['pathinfo'] = $routeExists ? Typecho_Router::url($type, $page) : '#';
			$page['permalink'] = Typecho_Common::url($page['pathinfo'], $options->index);
            array_push($urls,$page['permalink']);
		}
		foreach($articles AS $article) {
			$type = $article['type'];
			$article['categories'] = $db->fetchAll($db->select()->from('table.metas')
					->join('table.relationships', 'table.relationships.mid = table.metas.mid')
					->where('table.relationships.cid = ?', $article['cid'])
					->where('table.metas.type = ?', 'category')
					->order('table.metas.order', Typecho_Db::SORT_ASC));
			$article['category'] = urlencode(current(Typecho_Common::arrayFlatten($article['categories'], 'slug')));
			$article['slug'] = urlencode($article['slug']);
			$article['date'] = new Typecho_Date($article['created']);
			$article['year'] = $article['date']->year;
			$article['month'] = $article['date']->month;
			$article['day'] = $article['date']->day;
			$routeExists = (NULL != Typecho_Router::get($type));
			$article['pathinfo'] = $routeExists ? Typecho_Router::url($type, $article) : '#';
			$article['permalink'] = Typecho_Common::url($article['pathinfo'], $options->index);
            array_push($urls,$article['permalink']);
		}
        $api = sprintf('http://data.zz.baidu.com/urls?site=%s&token=%s', $site, $token);
        $ch = curl_init();
        $options1 =  array(
            CURLOPT_URL => $api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => implode("\n", $urls),
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options1);
        $result = curl_exec($ch);
        echo $result;
    }   
}
