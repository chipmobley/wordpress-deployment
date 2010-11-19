<?php $a = '<script type="text/javascript">var myReviewBadge = new Object();
myReviewBadge.isExample = false;
myReviewBadge.sites = new Array();
myReviewBadge.addSite = function(siteName, avgRating, ratingImg, totalReviews, siteLink){
  myReviewBadge.sites[siteName]           = new Object();  
  myReviewBadge.sites[siteName].name      = siteName;
  myReviewBadge.sites[siteName].rating    = parseInt(avgRating);
  myReviewBadge.sites[siteName].ratingImg = ratingImg;
  myReviewBadge.sites[siteName].count     = parseInt(totalReviews);
  
  if(isNaN(myReviewBadge.sites[siteName].rating)){
    myReviewBadge.sites[siteName].rating = 0;
  }
  if(isNaN(myReviewBadge.sites[siteName].count)){
    myReviewBadge.sites[siteName].count = 0;
  }

myReviewBadge.sites[siteName].url     = siteLink;
  myReviewBadge.sites[siteName].element = document.getElementById(\'myReview-\'+siteName);
  if(siteName == "yelp"){
      myReviewBadge.drawSiteYelp(siteName);
  } else {
      myReviewBadge.drawSite(siteName);
  }
}

myReviewBadge.drawSiteYelp = function(siteName){

  if(myReviewBadge.sites[siteName] && myReviewBadge.sites[siteName].element){
    var siteObj = myReviewBadge.sites[siteName];
    var mainEle = siteObj.element;
    //console.log(siteObj);
    
    var imgEle  = mainEle.getElementsByTagName(\'img\')[0];
    imgEle.style.border = "none";
    imgEle.style.verticalAlign = "text-top";
    
    mainEle.style.position = "relative";
    mainEle.style.fontFamily="Verdana, Arial, Helvetica, sans-serif";
    mainEle.style.width = "224px";
    mainEle.style.height = "122px";
    //mainEle.style.display = "inline-block";
    mainEle.style.marginBottom = "3px";
    mainEle.style.lineHeight = 1;
    if(siteObj.url == "0"){
      mainEle.style.display="none";
    }
    
    var logoImgEle = document.createElement(\'img\');
    logoImgEle.setAttribute(\'src\',\'http://static1.px.yelpcdn.com/static/200911301285253944/i/developers/Powered_By_Yelp_Red.png\');
    logoImgEle.setAttribute(\'alt\',\'Powered by yelp\');
    logoImgEle.style.border="none";
    var logoLinkEle = document.createElement(\'a\');
    logoLinkEle.href = "http://www.yelp.com";
    logoLinkEle.setAttribute(\'href\',"http://www.yelp.com");
    
    var linkEle   = document.createElement(\'a\');
    var titleEle  = document.createElement(\'div\');
    titleEle.innerHTML = "myReviews";
    titleEle.style.color="#FFFFFF";
    titleEle.style.textDecoration = "none";
    titleEle.style.fontSize="18px";
    titleEle.style.fontWeight="bold";
    titleEle.style.position ="absolute";
    titleEle.style.left = "0px";
    titleEle.style.top = "3px";
    titleEle.style.width = mainEle.style.width;
    titleEle.style.textAlign = "center";
    
    var ratingImgEle = document.createElement(\'img\');
    ratingImgEle.setAttribute(\'src\',siteObj.ratingImg);
    ratingImgEle.setAttribute(\'alt\',siteObj.rating+"/10");
    ratingImgEle.style.border="none";
    ratingImgEle.style.position ="absolute";
    ratingImgEle.style.left = "20px";
    ratingImgEle.style.top = "42px";
    var ratingTextEle = document.createElement(\'div\');
    ratingTextEle.innerHTML = siteObj.count+" Review";
    if(siteObj.count != 1){
      ratingTextEle.innerHTML += "s";
    }    
    ratingTextEle.style.fontSize="15px";
    ratingTextEle.style.border="none";
    ratingTextEle.style.position ="absolute";
    ratingTextEle.style.left = "107px";
    ratingTextEle.style.top = "40px";
    ratingTextEle.style.color="#CC3224";

    var getBadgeEle  = document.createElement(\'a\');
    getBadgeEle.innerHTML = "Get this badge";
    getBadgeEle.style.display="block";
    getBadgeEle.style.color="#CC3224";
    getBadgeEle.style.fontStyle = "italic";
    getBadgeEle.style.textDecoration = "none";
    getBadgeEle.style.fontSize="9px";
    getBadgeEle.style.position ="absolute";
    getBadgeEle.style.left = "14px";
    getBadgeEle.style.top = "88px";
    getBadgeEle.setAttribute(\'href\',\'http://www.myreviewspage.com/badges/\');
    
    linkEle.href = siteObj.url;
    linkEle.setAttribute(\'href\',siteObj.url);
    
    linkEle.style.textDecoration ="none";
    linkEle.style.color = "black";
    logoLinkEle.style.textDecoration ="none";
    logoLinkEle.style.color = "black";
    logoLinkEle.style.border = "none";
    logoLinkEle.style.position = "absolute";
    logoLinkEle.style.left = "94px";
    logoLinkEle.style.top = "80px";
    
    imgEle.setAttribute(\'src\',\'http://www.myreviewspage.com/images/badges/yelpframe.png\');
    
    
    
    mainEle.appendChild(linkEle);
    linkEle.appendChild(imgEle);
    linkEle.appendChild(titleEle);
    linkEle.appendChild(ratingImgEle);
    linkEle.appendChild(ratingTextEle);
    linkEle.appendChild(getBadgeEle);
    
    linkEle.appendChild(logoLinkEle);
    logoLinkEle.appendChild(logoImgEle);
  }
}  
  
myReviewBadge.drawSite = function(siteName){
  if(myReviewBadge.sites[siteName] && myReviewBadge.sites[siteName].element){
    var siteObj = myReviewBadge.sites[siteName];
    var mainEle = siteObj.element;
    //console.log(siteObj);
    
    var imgEle  = mainEle.getElementsByTagName(\'img\')[0];
    imgEle.style.border = "none";
    imgEle.style.verticalAlign = "text-top";
    
    mainEle.style.position = "relative";
    mainEle.style.fontFamily="Verdana, Arial, Helvetica, sans-serif";
    mainEle.style.width = "220px";
    mainEle.style.height = "50px";
    //mainEle.style.display = "inline-block";
    mainEle.style.marginBottom = "3px";
    mainEle.style.lineHeight = 1;

    
    if(siteObj.url == "0"){
      mainEle.style.display="none";
    }
 
    var linkEle   = document.createElement(\'a\');
    var countEle  = document.createElement(\'div\');
    var ratingEle = document.createElement(\'div\');
    linkEle.href = siteObj.url;
    linkEle.setAttribute(\'href\',siteObj.url);
    
    linkEle.style.textDecoration ="none";
    linkEle.style.color = "black";
    
    
    countEle.style.position="absolute";
    countEle.style.width="77px";
    countEle.style.textAlign="center";
    ratingEle.style.position="absolute";
    ratingEle.style.width="77px";
    ratingEle.style.textAlign="center";

    countEle.style.top="33px";
    countEle.style.fontSize="11px";
    if(siteObj.count >= 1000){
      countEle.style.fontSize="10px";
    }
    countEle.innerHTML = siteObj.count+" review";
    if(siteObj.count != 1){
      countEle.innerHTML += "s";
    }

    ratingEle.style.top="-3px";
    ratingEle.style.left="6px";
    ratingEle.style.fontSize="35px";
    ratingEle.innerHTML = siteObj.rating+"<span style=\'font-size:40%;\'>/10</span>";

    mainEle.appendChild(linkEle);
    linkEle.appendChild(imgEle);
    linkEle.appendChild(countEle);
    linkEle.appendChild(ratingEle);
  }
}

myReviewBadge.addSite(\'yelp\',\'3\',\'http://media4.px.yelpcdn.com/static/200911303106483837/i/ico/stars/stars_2_half.png\',\'1430\',\'http://yelp.com/biz/__I9HmtBMV4dDkEgT22V4g\');myReviewBadge.addSite(\'yahoo\',\'9\',\'//images/ratings/yahoo/09.png\',\'27\',\'http://local.yahoo.com/info-29515408-little-star-pizza-san-francisco\');myReviewBadge.addSite(\'google\',\'\',\'//images/ratings/google/150zoom/00.png\',\'297\',\'http://www.google.com/maps/place?cid=11972030858647125931&amp;q=(415)+441-1118+Little+Star+Pizza+&amp;cd=1&amp;cad=src:pplink&amp;ei=6CgpTNH1F4PiiwPQv6CtDw\');myReviewBadge.addSite(\'citysearch\',\'9\',\'//images/ratings/citysearch/09.png\',\'60\',\'0\');myReviewBadge.addSite(\'bing\',\'7.744999\',\'http://www.myreviewspage.com/images/ratings/bing/150zoom/01.png\',\'61\',\'http://www.bing.com/local/Details.aspx?lid=YN114x2053665\');myReviewBadge.addSite(\'foursquare\',\'0\',\'\',\'0\',\'0\');
</script>'?>
