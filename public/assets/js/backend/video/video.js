define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/video/index',
                    add_url: 'video/video/add',
                    edit_url: 'video/video/edit',
                    del_url: 'video/video/del',
                    multi_url: 'video/video/multi',
                    table: 'video',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'key', title: __('Key')},
                        {field: 'hash', title: __('Hash')},
                        {field: 'category_id', title: __('Category_id')},
                        {field: 'original_bit_rate', title: __('Original_bit_rate'), operate:'BETWEEN'},
                        {field: 'direction', title: __('Direction')},
                        {field: 'duration', title: __('Duration'), operate:'BETWEEN'},
                        {field: 'weight', title: __('Weight')},
                        {field: 'width', title: __('Width'), operate:'BETWEEN'},
                        {field: 'height', title: __('Height'), operate:'BETWEEN'},
                        {field: 'lng', title: __('Lng'), operate:'BETWEEN'},
                        {field: 'lat', title: __('Lat'), operate:'BETWEEN'},
                        {field: 'recommend', title: __('Recommend')},
                        {field: 'save_original_bkt', title: __('Save_original_bkt')},
                        {field: 'bgmusic_id', title: __('Bgmusic_id')},
                        {field: 'bgmusic_name', title: __('Bgmusic_name')},
                        {field: 'exists_cover_img', title: __('Exists_cover_img')},
                        {field: 'user_view_total', title: __('User_view_total')},
                        {field: 'user_share_total', title: __('User_share_total')},
                        {field: 'user_like_total', title: __('User_like_total')},
                        {field: 'user_comment_total', title: __('User_comment_total')},
                        {field: 'real_user_like_total', title: __('Real_user_like_total')},
                        {field: 'real_user_comment_total', title: __('Real_user_comment_total')},
                        {field: 'source_link_id', title: __('Source_link_id')},
                        {field: 'is_competition_production', title: __('Is_competition_production')},
                        {field: 'source_video_id', title: __('Source_video_id')},
                        {field: 'forward_from_video_id', title: __('Forward_from_video_id')},
                        {field: 'direct_forward_total', title: __('Direct_forward_total')},
                        {field: 'all_forward_total', title: __('All_forward_total')},
                        {field: 'geohash', title: __('Geohash')},
                        {field: 'less_geohash', title: __('Less_geohash')},
                        {field: 'public_video_transcode', title: __('Public_video_transcode')},
                        {field: 'private_video_transcode', title: __('Private_video_transcode')},
                        {field: 'need_private_video_transcode', title: __('Need_private_video_transcode')},
                        {field: 'process_status', title: __('Process_status')},
                        {field: 'process_done_time', title: __('Process_done_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status')},
                        {field: 'fix_weight', title: __('Fix_weight')},
                        {field: 'last_update_weight', title: __('Last_update_weight')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});