const RowsPerPage = 5;
const ColsPerRow = 6;
const pageNumbers = document.getElementById("pageNumbers");

var totalPages = null;

var sSearchTagList = [];
var sSelectedList = [];


function setSearchTagList(taglist) {
    sSearchTagList = taglist;
}

function getSearchTagList() {
    return sSearchTagList;
}

function setSelectedList(newSelectedList) {
    sSelectedList = newSelectedList;
}

function getSelectedList() {
    return sSelectedList;
}



function forceRedraw(element) {
    if (!element) { return; }

    var disp = element.style.display;  // get a copy of the previous display style
    element.offsetHeight; // just reference as part of redraw hack on some browsers 
    element.style.display = 'none';
    
    setTimeout(function(){
        element.style.display = disp;
    },50); // you can play with this timeout to make it as short as possible
}


function setActivePageNumber() {
    forceRedraw(document.getElementById("myTableParent"));
    document.querySelectorAll("#pageNumbers a").forEach(a=>{
        if(a.innerText == currentPage) {
            a.classList.add("active");
        }
    });
}


function createSearchPageLink(linkText, resultset, pageNumber) {
    let pageLink = document.createElement("a");
    pageLink.href = "#";
    pageLink.innerHTML = linkText;
    
    pageLink.addEventListener("click", function(e){
	changeSearchPage(e, resultset, pageNumber);
	
	var f = parent.document.getElementById("imgArrayFrame");
	f.clearChecksAction();
    });
    pageNumbers.appendChild(pageLink);
}


function paginate(resultset, pageIdx, numItems, onCompletion) {
    currentPage = pageIdx+1; // not zero-based

    if (!numItems) numItems = totalTableEntries.innerHTML;
    totalPages = Math.trunc(numItems/RowsPerPage/ColsPerRow)+1;
    
    let table = document.getElementById("myTable");
    let rows = Array.from(table.rows).slice(1);
    
    rows.forEach(row=>row.style.display="none");
    
    let start = (currentPage - 1) * RowsPerPage;
    let end = start + RowsPerPage;
    rows.slice(start,end).forEach(row=>row.style.display = "");
    
    pageNumbers.innerHTML = "";
    createSearchPageLink("<<", resultset, 1);
    createSearchPageLink("<", resultset, currentPage-1);
    
    let startPageNumber = currentPage < 5 ? 1 : (currentPage>totalPages-2?totalPages-4 : currentPage-2);
    let endPageNumber =totalPages<5 ? totalPages : (currentPage<=totalPages -2 ? startPageNumber+4 : totalPages);
    for (let i=startPageNumber;i<=endPageNumber;i++) {
	createSearchPageLink(i, resultset, i);
    }
    createSearchPageLink(">", resultset, currentPage+1);
    createSearchPageLink(">>", resultset, totalPages);
    
    setActivePageNumber();
    
    from.innerHTML = (currentPage-1)*RowsPerPage*ColsPerRow+1;
    to.innerHTML = currentPage === totalPages ? numItems : (currentPage)*RowsPerPage*ColsPerRow;
    totalTableEntries.innerHTML = numItems;

    if (onCompletion)
	onCompletion();
}


function setImages0(page, dburl, query, onCompletion) {
    fetch(dburl+query)
	.then(res => {
	    if (!res.ok) {
		console.log("ERROR(setImages0): network error");
	    } else {
		return res.json();
	    }
	})
	.then(data => {
	    var imageCnt = 0;
	    for (var i = 0; i < data.rows.length; i++) {
		if ((i+data.offset >= page*RowsPerPage*ColsPerRow) &&
		    (i+data.offset < (page+1)*RowsPerPage*ColsPerRow)) {
		    var img = document.getElementById("image"+imageCnt);
		    img.src = dburl+"/"+data.rows[i].id+"/thumbnail";
		    img.setAttribute("data-objid", data.rows[i].id);
		    img.setAttribute("data-firstrow", (page*RowsPerPage));
		    img.style.visibility = "visible";

		    var check = document.getElementById("check"+imageCnt);
		    check.checked = false;

		    var label = document.getElementById("label"+imageCnt);
		    label.style.visibility = "visible";

		    imageCnt += 1;
		}
	    }
	    while (imageCnt < RowsPerPage*ColsPerRow) {
		var img = document.getElementById("image"+imageCnt);
		img.src = "img/transparent.png";
		img.setAttribute("data-objid", null);
		img.style.visibility = "hidden";

		var check = document.getElementById("check"+imageCnt);
		check.checked = false;

		var label = document.getElementById("label"+imageCnt);
		label.style.visibility = "hidden";

		imageCnt += 1;
	    }

	    onCompletion(data.total_rows);
	})
	.catch(error => {
	    console.log("ERROR(setImages0): Fetch error: "+error);
	});
}


function updateTableRendering(visibleSet, pageIdx, dburl) {
    var imageCnt = 0;
    visibleSet.forEach(r => {
        var img = document.getElementById("image"+imageCnt);
	img.src = dburl+"/"+r+"/thumbnail";
	img.setAttribute("data-objid", r);
	img.setAttribute("data-firstrow", (pageIdx*RowsPerPage));
	img.style.visibility = "visible";
	
	var check = document.getElementById("check"+imageCnt);
	check.checked = false;
	
	var label = document.getElementById("label"+imageCnt);
	label.style.visibility = "visible";
	
	imageCnt += 1;
    });
    while (imageCnt < RowsPerPage*ColsPerRow) {
	var img = document.getElementById("image"+imageCnt);
	img.src = "img/transparent.png";
	img.setAttribute("data-objid", null);
	img.style.visibility = "hidden";
	
	var check = document.getElementById("check"+imageCnt);
	check.checked = false;
	
	var label = document.getElementById("label"+imageCnt);
	label.style.visibility = "hidden";
	
	imageCnt += 1;
    }
}


function getVisibleSubset(resultset, offset) {
    const pageIdx = Math.trunc(offset/RowsPerPage/ColsPerRow);

    var visibleSet = [];
    for (var i = pageIdx*RowsPerPage*ColsPerRow; i < resultset.length; i++)
	if (i < (pageIdx+1)*RowsPerPage*ColsPerRow) 
	    visibleSet.push(resultset[i]);

    return visibleSet;
}


function changeSearchPage(e, resultset, pageNumber) {
    if (e !== null) 
	e.preventDefault();
    
    pageNumberInput.value = "";

    var numItems = totalTableEntries.innerHTML;
    if (totalPages === null) 
	totalPages = Math.trunc(numItems/RowsPerPage/ColsPerRow)+1;
    
    if((pageNumber <= 0)||(pageNumber>totalPages))
	return;

    const pageIdx = pageNumber-1;
    const perpage = RowsPerPage*ColsPerRow;
    const offset = pageIdx*perpage;
    
    const dburl = document.getElementById("dbUrl").innerHTML.trim();
    
    if ((resultset === null) || (resultset.length === 0)) {
	setImages0(pageIdx, dburl,
               "/_design/photos/_view/photo_ids?descending=false&limit="+perpage+"&skip="+offset,
               function onCompletion() {paginate(null, pageIdx, numItems);});
    } else {
	updateTableRendering(getVisibleSubset(resultset, offset), pageIdx, dburl);
	paginate(resultset, pageIdx, resultset.length);

	var f = parent.document.getElementById("imgArrayFrame");
	f.clearChecksAction();
    }
}


function collectResultset(resultset, packetIdx, baseSearchurl, bookmark, onCompletion) {
    var searchurl = baseSearchurl + (!!bookmark ? "&bookmark="+bookmark : "");
    fetch(searchurl)
	.then(res => {
	    if (!res.ok)
		console.log("ERROR(findImages0): network error");
	    else 
		return res.json();
	})
	.then(data => {
	    data.rows.forEach(r => {resultset.push(r.id);});
	    if (resultset.length < data.total_rows) {
		collectResultset(resultset, packetIdx+1, baseSearchurl, data.bookmark, onCompletion);
	    } else {
		if (onCompletion) 
		    onCompletion(resultset);
	    }
	})
	.catch(error => {
	    console.log("ERROR(findImages0): Fetch error: "+error);
	});
}


function prettyPrintTagList(tagList) {
    var str = null;
    tagList.forEach(tag => {
	if (str === null) str = tag;
	else str += " "+tag;
    });
    return str === null ? "" : str;
}


function onFindImagesButton(onCompletion) {
    const dburl = document.getElementById("dbUrl").innerHTML.trim();
    var tags = getSearchTagList();
    if (tagInput.value && !tags.includes(tagInput.value)) {
	tags.push(tagInput.value);
	setSearchTagList(tags);
    }
    tagList.value = prettyPrintTagList(tags);
    tagInput.value = null;

    const PacketSz = 100;
    var searchurl = dburl+"/_design/photos_by_tag/_search/photos_by_tag?limit="+PacketSz+"&q=";
    tags.forEach((tag,i) => {if (i>0) searchurl += " AND "; searchurl += tag;});
    //like: "http://HOST:5984/photos/_design/photos_by_tag/_search/photos_by_tag?limit=200&q=man AND woman";

    collectResultset([], 0, searchurl, null, resultset => {
	setSelectedList(resultset);

	const offset = 0;
	const pageIdx = 0;
	updateTableRendering(getVisibleSubset(resultset, offset), pageIdx, dburl);

	paginate(resultset, offset, resultset.length);
	if (onCompletion) 
	    onCompletion();
    });
}


goToPageButton.addEventListener("click",(e)=>{
    e.preventDefault();

    if ((getSearchTagList() !== null) && (getSearchTagList().length > 0)) {
	changeSearchPage(e, getSelectedList(), pageNumberInput.value);
    } else {
	changeSearchPage(e, null, pageNumberInput.value);
    }
    
    var f = parent.document.getElementById("imgArrayFrame");
    f.clearChecksAction();
    
    pageNumberInput.value = ""
});


findImagesButton.addEventListener("click",(e)=>{
    e.preventDefault();

    onFindImagesButton(function onCompletion() {});
});


clearFindButton.addEventListener("click",(e)=>{
    e.preventDefault();

    setSelectedList([]);
    setSearchTagList([]);
    
    var f = parent.document.getElementById("imgArrayFrame");
    f.clearChecksAction();
    
    tagInput.value = null;
    tagList.value = null;
    changeSearchPage(e, null, "1");
});


tagInput.addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
        event.preventDefault();
	onFindImagesButton(function onCompletion() {
	    setTimeout(function(){
		tagInput.focus();
	    },50); // you can play with this timeout to make it as short as possible
	});
    }
});
