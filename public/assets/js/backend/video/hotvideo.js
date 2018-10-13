define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/hotvideo/index',
                    // add_url: 'video/hotvideo/add',
                    // edit_url: 'video/hotvideo/edit',
                    // del_url: 'video/hotvideo/del',
                    // multi_url: 'video/hotvideo/multi',
                    table: 'hot_video',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                search: false,
                showToggle: false,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'hot_video.id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'video.title', title: __('视频标题')},
                        {field: 'admin.nickname', title: __('Admin_id')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'status',
                            title: __('Status'),
                            formatter: function(data) {
                                if (data == 1) {
                                    return '正常';
                                } else {
                                    return '取消';
                                }
                            },
                            searchList: 
                            {
                                '1': __('正常'),
                                '0': __('取消'),
                            },
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            buttons: [
                                {
                                    name: 'play',
                                    title: __('查看视频'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-play',
                                    url: 'video/hotvideo/play'
                                },
                                {
                                    name: 'unhost',
                                    title: __('取消推荐'),
                                    text: __('取消推荐'),
                                    classname: 'btn btn-xs btn-warning btn-unhost',
                                    hidden: function(row) {
                                        if (row.status == '0') {
                                            return true;
                                        }
                                    }
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
                        e.stopPropagation();
                        e.preventDefault();
                        var that = this;
                        var top = $(that).offset().top - $(window).scrollTop();
                        var left = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (top + 154 > $(window).height()) {
                            top = top - 154;
                        }
                        if ($(window).width() < 480) {
                            top = left = undefined;
                        }
                        Layer.confirm(
                            __('Are you sure you want to delete this item?'),
                            {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                            function (index) {
                                var table = $(that).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Table.api.multi("del", row[options.pk], table, that);
                                Layer.close(index);
                            }
                        );
                    },
                    // 取消推荐
                    'click .btn-unhost': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var that = this;
                        var top = $(that).offset().top - $(window).scrollTop();
                        var left = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (top + 154 > $(window).height()) {
                            top = top - 154;
                        }
                        if ($(window).width() < 480) {
                            top = left = undefined;
                        }
                        Layer.confirm(
                            __('确定要取消推荐吗?'),
                            {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                            function (index) {
                                var table = $(that).closest('table');
                                Fast.api.ajax({
                                    url: 'video/video/hide',
                                    type: 'POST',
                                    data: {
                                        id: row.video_id
                                    }
                                }, function (data, result) {
                                    Layer.close(index);
                                }, function (data, result) {
                                    Layer.close(index);
                                })
                            }
                        );
                    },
                }
            },
            formatter: {
                operate: function (value, row, index) {
                    var table = this.table;
                    // 操作配置
                    var options = table ? table.bootstrapTable('getOptions') : {};
                    // 默认按钮组
                    var buttons = $.extend([], this.buttons || []);
                    // 所有按钮名称
                    var names = [];
                    buttons.forEach(function (item) {
                        names.push(item.name);
                    });
                    if (options.extend.del_url !== '' && names.indexOf('del') === -1) {
                        buttons.push({
                            name: 'del',
                            icon: 'fa fa-trash',
                            title: __('Del'),
                            extend: 'data-toggle="tooltip"',
                            classname: 'btn btn-xs btn-danger btn-delone'
                        });
                    }
                    return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                }
            }
        }
    };
    return Controller;
});