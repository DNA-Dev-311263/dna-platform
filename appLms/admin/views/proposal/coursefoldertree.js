var CourseFolderTree = function(id, oConfig) {

	CourseFolderTree.superclass.constructor.call(this, id, oConfig);

	this.setNodeClickEvent(this.refreshTable, this);


};

YAHOO.lang.extend(CourseFolderTree, FolderTree, {

	refreshTable: function(oNode) {
		setCategory(this._getNodeId(oNode));
    }
});
