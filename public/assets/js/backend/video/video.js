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
                search: false,
                showToggle: false,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title')},
                        {field: 'user_id', title: __('User_id'), operate: false},
                        {field: 'category_text', title: __('category_text'), operate: false, },
                        {field: 'category_id', title: __('Category_id'), visible: false},
                        {field: 'user_view_total', title: __('User_view_total'), operate: false},
                        {field: 'user_like_total', title: __('User_like_total'), operate: false},
                        {field: 'user_comment_total', title: __('User_comment_total'), operate: false},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, operate: 'RANGE'},
                        {field: 'status_text', title: __('Status'), operate: false},
                        {
                            field: 'status',
                            title: __('Status'), 
                            searchList: 
                                {
                                    '-1': __('删除'),
                                    '0': __('未发布'),
                                    '1': __('已发布'),
                                    '2': __('机器审核未通过'),
                                    '3': __('违规'),
                                    '8': __('机器审核通过'),
                                    '9': __('审核不通过'),
                                    '10': __('草稿')
                                },
                            visible: false
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                             buttons: [
                                {
                                    name: 'set_category',
                                    title: __('设置分类'),
                                    text: __('设置分类'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'video/video/set_category'
                                },
                                {
                                    name: 'check_video',
                                    title: __('审核'),
                                    text: __('审核'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'video/video/check_video',
                                    hidden: function(row) {
                                        if (row.status != '2') {
                                            return true;
                                        }
                                    }
                                },
                                {
                                    name: 'add_like_total',
                                    title: __('增加点赞数'),
                                    text: __('增加点赞数'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'video/video/add_like_total'
                                }
                            ],
                            formatter: Table.api.formatter.operate
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
        set_category: function () {
            Controller.api.bindevent();
        },
        check_video: function () {
            Controller.api.bindevent();
        },
        add_like_total: function () {
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