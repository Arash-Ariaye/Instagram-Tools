<?php
header('Content-type: application/json');
$_COOKIE = '';
class Instagram
{
    function igInfo($user)
    {
        $request = curl_init();
        curl_setopt_array($request, array(
            CURLOPT_URL => sprintf('https://www.instagram.com/%s/?__a=1', $user),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.71 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Cache-Control: max-age=0',
                'Cookie: ' . $_COOKIE
            )
        ));
        $exec = curl_exec($request);
        $response = json_decode($exec);
        curl_close($request);
        if ($exec != '{}') {
            $user = $response->graphql->user;
            $json = [
                "status" => true,
                "name" => $user->full_name,
                "id" => $user->id,
                "bio" => $user->biography,
                "website" => $user->external_url, "account" => ["business" => $user->is_business_account, "professional" => $user->is_professional_account, "category" => $user->category_name],
                "profile_hd" => $user->profile_pic_url_hd,
                "followers" => $user->edge_followed_by->count,
                "following" => $user->edge_follow->count
            ];
            return json_encode($json, 128);
        } else {
            return json_encode(['status' => false], 128);
        }
    }

    function igSave($url)
    {
        $request = curl_init();
        curl_setopt_array($request, array(
            CURLOPT_URL => sprintf('%s&__a=1', $url),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_COOKIEFILE => 'cookies.txt',
            CURLOPT_COOKIEJAR => 'cookies.txt',
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.71 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Cache-Control: max-age=0',
                'Cookie: ' . $_COOKIE
            )
        ));
        $exec = curl_exec($request);
        $response = json_decode($exec);
        curl_close($request);
        if ($exec != '{}') {
            $data = $response->graphql->shortcode_media;
            if ($data->__typename == 'GraphVideo') {
                if (empty($data->edge_media_to_caption->edges[0]->node->text))
                    return json_encode(['status' => true, 'type' => 'video', 'caption' => null, 'file' => $data->video_url], 128);
                else
                    return json_encode(['status' => true, 'type' => 'video', 'caption' => $data->edge_media_to_caption->edges[0]->node->text, 'file' => $data->video_url], 128);
            } elseif ($data->__typename == 'GraphImage') {
                if (empty($data->edge_media_to_caption->edges[0]->node->text))
                    return json_encode(['status' => true, 'type' => 'image', 'caption' => null, 'file' => $data->display_url], 128);
                else
                    return json_encode(['status' => true, 'type' => 'image', 'caption' => $data->edge_media_to_caption->edges[0]->node->text, 'file' => $data->display_url], 128);
            } elseif ($data->__typename == 'GraphSidecar') {
                $childrens = [];
                foreach ($data->edge_sidecar_to_children->edges as $child) {
                    if ($child->node->__typename == 'GraphVideo') {
                        array_push($childrens, ['type' => $child->node->__typename, 'file' => $child->node->video_url]);
                    } else {
                        array_push($childrens, ['type' => $child->node->__typename, 'file' => $child->node->display_url]);
                    }
                }
                if (empty($data->edge_media_to_caption->edges[0]->node->text))
                    return json_encode(['status' => true, 'type' => 'side', 'caption' => null, 'data' => $childrens], 128);
                else
                    return json_encode(['status' => true, 'type' => 'side', 'caption' => $data->edge_media_to_caption->edges[0]->node->text, 'data' => $childrens], 128);
            }
        } else {
            return json_encode(['status' => false], 128);
        }
    }
}
$instagram = new Instagram();
if (isset($_GET['user']) && !empty($_GET['user'])) {
    echo $instagram->igInfo($_GET['user']);
} elseif (isset($_GET['link']) && !empty($_GET['link'])) {
    echo $instagram->igSave($_GET['link']);
}
