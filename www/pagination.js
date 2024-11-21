const RowsPerPage = 5;
const ColsPerRow = 5;
const pageNumbers = document.getElementById("pageNumbers");

var totalPages = null;
var searchResultset = null;

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
    });
    pageNumbers.appendChild(pageLink);
}


function paginate(resultset, pageIdx, numItems) {
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

		    var f = parent.document.getElementById("imgArrayFrame");
		    f.clearChecksAction();

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
    
    var f = parent.document.getElementById("imgArrayFrame");
    f.clearChecksAction();
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
    if((pageNumber <= 0)||(pageNumber>totalPages))
	return;
    
    e.preventDefault();
    pageNumberInput.value = "";

    const pageIdx = pageNumber-1;
    const perpage = RowsPerPage*ColsPerRow;
    const offset = pageIdx*perpage;

    const dburl = document.getElementById("dbUrl").innerHTML.trim();
    
    if (resultset === null) {
	setImages0(pageIdx, dburl,
               "/_design/photos/_view/photo_ids?descending=false&limit="+perpage+"&skip="+offset,
               function onCompletion(numItems) {
		   paginate(null, pageIdx, numItems);
	       });
    } else {
	updateTableRendering(getVisibleSubset(resultset, offset), pageIdx, dburl);
	paginate(resultset, pageIdx, resultset.length);
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

	    if (resultset.length < data.total_rows) 
		collectResultset(resultset, packetIdx+1, baseSearchurl, data.bookmark, onCompletion);
	    else 
		onCompletion(resultset);
	})
	.catch(error => {
	    console.log("ERROR(findImages0): Fetch error: "+error);
	});
}


goToPageButton.addEventListener("click",(e)=>{
    e.preventDefault();

    changeSearchPage(e, searchResultset, pageNumberInput.value);
    pageNumberInput.value = ""
});


findImagesButton.addEventListener("click",(e)=>{
    e.preventDefault();

    const dburl = document.getElementById("dbUrl").innerHTML.trim();
    const searchTerm = tagInput.value;

    const PacketSz = 100;
    const searchurl = dburl+"/_design/photos_by_tag/_search/photos_by_tag?limit="+PacketSz+"&q="+searchTerm;
    //like: "http://mediaserver:5984/photos/_design/photos_by_tag/_search/photos_by_tag?limit=200&q=cherry";

    collectResultset([], 0, searchurl, null, function onCompletion (resultset) {
	console.log("DEBUG(findImagesButton): resultset.length: "+resultset.length);

	searchResultset = resultset;

	const offset = 0;
	const pageIdx = 0;
	updateTableRendering(getVisibleSubset(resultset, offset), pageIdx, dburl);
	
	paginate(resultset, offset, resultset.length);
    });
});


clearFindButton.addEventListener("click",(e)=>{
    e.preventDefault();

    searchResultset = null;
    tagInput.value = ""
    changeSearchPage(e, null, "1");
});

