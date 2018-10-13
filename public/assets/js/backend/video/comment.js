define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/comment/index',
                    // add_url: 'video/comment/add',
                    // edit_url: 'video/comment/edit',
                    // del_url: 'video/comment/del',
                    // multi_url: 'video/comment/multi',
                    table: 'video_comment',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                search: false,
                showToggle: false,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'video_comment.id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'video_comment.id', title: __('Id')},
                        {field: 'video.title', title: __('视频标题')},
                        {field: 'user.nickname', title: __('用户昵称')},
                        {field: 'video_comment.video_comment', title: __('Video_comment'), operate: false},
                        {field: 'video_comment.replace_comment', title: __('Replace_comment'), operate: false},
                        {field: 'video_comment.create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'video_comment.update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'video_comment.status', 
                            title: __('Status'), 
                            formatter: function (data) {
                                if (data == 1) {
                                    return '显示';
                                } else {
                                    return '隐藏';
                                }
                            },
                            searchList: {
                                '1': __('显示'),
                                '0': __('隐藏'),
                            }
                        },
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
                                        if (row.video_comment.status == '0') {
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
                                        if (row.video_comment.status == '1') {
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
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            events: {
                operate: {
                    'click .btn-delone': function (e, value, row, index) {
                        var that = this;
                        Layer.confirm(
                            __('Are you sure you want to delete this item?'),
                            {icon: 3, title: __('Warning'), offset: Controller.api.method.windowSize(that), shadeClose: true},
                            function (index) {
                                var table = $(that).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Table.api.multi("del", row[options.pk], table, that);
                                Layer.close(index);
                            }
                        );
                    },
                    // 隐藏
                    'click .btn-hide': function (e, value, row, index) {
                        layer.prompt(
                            {icon: 3, title: '请填写备注信息', offset: Controller.api.method.windowSize(this), shadeClose: true}, 
                            function (text, index) {
                                Controller.api.method.sendAjax(
                                    index,
                                    'video/comment/hide',
                                    {
                                        replace_comment: text,
                                        id: row.video_comment.id
                                    }
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
                                Controller.api.method.sendAjax(index, 'video/comment/show', {id: row.video_comment.id});
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