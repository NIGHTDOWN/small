define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/comment/index',
                    table: 'video_comment',
                }
            });

            var table = $("#table");
            var statusText = {0: '隐藏', 1: '显示'};
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
                        {field: 'video.title', title: __('视频标题')},
                        {field: 'user.nickname', title: __('用户昵称')},
                        {field: 'video_comment', title: __('Video_comment'), operate: false},
                        {field: 'replace_comment', title: __('Replace_comment'), operate: false},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), formatter: function (data) {return statusText[data];}, searchList: statusText},
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            table: table, 
                            buttons: [
                                {
                                    name: 'hide',
                                    text: __('隐藏'),
                                    title: __('隐藏'),
                                    classname: 'btn btn-xs btn-danger btn-hide',
                                    hidden: function (row) {
                                        if (row.status == '0') {
                                            return true;
                                        }
                                    },
                                },
                                {
                                    name: 'show',
                                    text: __('显示'),
                                    title: __('显示'),
                                    classname: 'btn btn-xs btn-success btn-show',
                                    hidden: function (row) {
                                        if (row.status == '1') {
                                            return true;
                                        }
                                    },
                                }
                            ],
                            
                            events: Controller.api.events.operate, 
                            formatter: Controller.api.formatter.operate
                        }
                    ]
                ]
            });

            

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            events: {
                operate: {
                    // 隐藏
                    'click .btn-hide': function (e, value, row, index) {
                        layer.prompt(
                            {icon: 3, title: '请填写备注信息', offset: Controller.api.method.windowSize(this), shadeClose: true}, 
                            function (text, index) {
                                Controller.api.method.sendAjax(index, 'video/comment/hide', {replace_comment: text, id: row.id}
                                );
                            }
                        );
                    },
                    // 显示
                    'click .btn-show': function (e, value, row, index) {
                        Layer.confirm(
                            __('确定要显示吗?'),
                            {icon: 3, title: __('Warning'), offset: Controller.api.method.windowSize(this), shadeClose: true},
                            function (index) {
                                Controller.api.method.sendAjax(index, 'video/comment/show', {id: row.id});
                            }
                        );
                    }
                }
            },
            formatter: {
                operate: function (value, row, index) {
                    var table = this.table;
                    // 操作配置
                    var options = table ? table.bootstrapTable('getOptions') : {};
                    // 默认按钮组
                    var buttons = $.extend([], this.buttons || []);
                    return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                }
            },
            method: {
                windowSize: function (obj) {
                    var top = $(obj).offset().top - $(window).scrollTop();
                    var left = $(obj).offset().left - $(window).scrollLeft() - 260;
                    if (top + 154 > $(window).height()) {
                        top = top - 154;
                    }
                    if ($(window).width() < 480) {
                        top = left = undefined;
                    }
                    return [top, left];
                },
                sendAjax: function (index, url, data = {}) {
                    Fast.api.ajax({
                        url: url,
                        type: 'POST',
                        data: data
                    }, function (data, result) {
                        Layer.close(index);
                        $('.btn-refresh').trigger('click');
                    }, function (data, result) {
                        Layer.close(index);
                    })
                }
            }
        }
    };
    return Controller;
});