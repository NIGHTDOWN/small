define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'opinion/feedback/index',
                    // edit_url: 'opinion/feedback/edit',
                    multi_url: 'opinion/feedback/multi',
                    reply: 'opinion/feedback/reply',
                    table: 'opinion_feedback',
                }
            });

            var table = $("#table");
            var statusText = {0: '未回复', 1: 'app已读', 2: '已回复'};
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
                        {field: 'user.nickname', title: __('用户昵称'), operate: false},
                        {field: 'content', title: __('Content'), operate: 'LIKE'},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'reply_status', title: __('Reply_status'), searchList: statusText, formatter: function(data){return statusText[data];}},
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            buttons: [
                                {
                                    name: 'detail',
                                    text: __('详情'),
                                    title: __('详情'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'opinion/feedback/detail',
                                },
                                {
                                    name: 'reply',
                                    text: __('回复'),
                                    title: __('回复'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'opinion/feedback/reply',
                                }
                            ],
                            table: table, events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            

            // 为表格绑定事件
            Table.api.bindevent(table);
            // 自动义按钮
            Controller.api.buttons(table);
        },
        reply: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            buttons: function (table) {
                // Bootstrap-table的父元素,包含table,toolbar,pagnation
                var parenttable = table.closest('.bootstrap-table');
                // Bootstrap-table配置
                var options = table.bootstrapTable('getOptions');
                // Bootstrap操作区
                var toolbar = $(options.toolbar, parenttable);
                // 回复文案
                $(toolbar).on('click', '.btn-default_list', function () {
                    Fast.api.open('opinion/feedback/default_list', __('回复文案'));
                });
            }
        }
    };
    return Controller;
});