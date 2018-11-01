define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'site/sitebanner/index',
                    add_url: 'site/sitebanner/add',
                    edit_url: 'site/sitebanner/edit',
                    del_url: 'site/sitebanner/del',
                    multi_url: 'site/sitebanner/multi',
                    table: 'xyx_site_banner',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false ,
//启用普通表单搜索
                commonSearch: true,
//可以控制是否默认显示搜索单表,false则隐藏,默认为false
                searchFormVisible: true,
                columns: [
                    [
                        {field: 'id', title: __('Id'),operate:false},
                        {
                            field: 'site_banner_type.type',
                            title: __('类型'),operate:false,
                        },
                        {field: 'image', title: __('Image'), formatter: Table.api.formatter.image,operate:false},
                        {field: 'status', title: '状态',formatter:Table.api.formatter.toggle,yes:'1',no:'0',operate:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
                        {
                            field: 'client_type',
                            title: '客户端类型',
                            searchList:
                                {
                                    '1': __('pc'),
                                    '0': __('手机'),
                                },
                            visible:false
                        },
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