define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity/topdata/index',
                    // add_url: 'activity/topdata/add',
                    // edit_url: 'activity/topdata/edit',
                    // del_url: 'activity/topdata/del',
                    // multi_url: 'activity/topdata/multi',
                    table: 'activity_top_data',
                },
                queryParams: function (params) {
                    var activity_id=Fast.api.query('activity_id');
                    if (activity_id){
                        var filter = JSON.parse(params.filter);
                        var op = JSON.parse(params.op);
                        filter.activity_id=activity_id;
                        op.activity_id='=';
                        params.filter=JSON.stringify(filter);
                        params.op=JSON.stringify(op);
                    }
                    return params;
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
                        {field: 'id', title: __('Id'),sortable:true},
                        {field: 'video.title', title: __('视频标题'),operate:'like'},
                        {field: 'user.nickname', title: __('用户昵称'),column:'user_id',addclass:'selectpage', data:'data-source="user/user/selectpage"  data-params=""  data-field="nickname"'},
                        {field: 'video_play', title: __('播放数'),sortable:true,operate:'between'},
                        {field: 'video_apply', title: __('评论数'),sortable:true,operate:'between'},
                        {field: 'video_like', title: __('点赞数'),sortable:true,operate:'between'},
                        {field: 'create_time', title: __('创建时间'),sortable:true, operate:'range', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: 'play',
                                    title: __('查看视频'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-play',
                                    url: 'activity/activity/play/video_id/{video_id}'
                                }
                            ]
                        }
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