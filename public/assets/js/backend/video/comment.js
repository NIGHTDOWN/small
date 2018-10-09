define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/comment/index',
                    add_url: 'video/comment/add',
                    edit_url: 'video/comment/edit',
                    del_url: 'video/comment/del',
                    multi_url: 'video/comment/multi',
                    table: 'video_comment',
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
                        {field: 'video_id', title: __('Video_id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'video_comment', title: __('Video_comment')},
                        {field: 'replace_comment', title: __('Replace_comment')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'is_parent', title: __('Is_parent')},
                        {field: 'is_robot', title: __('Is_robot')},
                        {field: 'like_total', title: __('Like_total')},
                        {field: 'status', title: __('Status')},
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